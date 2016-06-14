<?php
namespace Selenia\Matisse\Components\Base;

use Selenia\Interfaces\RenderableInterface;
use Selenia\Interfaces\Views\ViewInterface;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Traits\Component\ViewModelTrait;
use Selenia\ViewEngine\Engines\MatisseEngine;

/**
 * A component that delegates its rendering to a separate template (either internal or external to the component),
 * which is parsed, compiled and (in some cases) rendered by a view engine.
 *
 * <p>Composite components are composed of both a "source DOM" and a view (or "shadow DOM").
 *
 * <p>The source DOM is the set of original DOM subtrees (from children or from properties) provided to the component
 * on the document by its author. It can be used to provide metadata and/or document fragments for inclusion on the
 * view. This is the DOM that simple (non-composite) components work with.
 *
 * <p>Composite components do not render themselves directly, instead they delegate rendering to a view, which parses,
 * compiles and renders a template with the help of a view engine.
 *
 * <p>The view engine can be Matisse, in which case the view is compiled into a "shadow DOM" of components that can
 * render themselves, or it can be another templating engine, which usually is also responsible for rendering the
 * template.
 *
 * > <p>**Note:** Matisse components on the view can, in turn, be composite components that have their own templates,
 * and so on recursively. **But** the rendered output of a composite component must be final rendered markup, it can
 * not be again a template that requires further processing.
 */
class CompositeComponent extends Component
{
  use ViewModelTrait;

  /**
   * An inline/embedded template to be rendered as the component's appearance.
   *
   * <p>The view engine to be used to handle the template is selected by {@see $viewEngineClass}.
   *
   * @var string
   */
  public $template = '';
  /**
   * The URL of an external template to be loaded and rendered.
   *
   * <p>If specified, it takes precedence over {@see $template}.
   * <p>The view engine to be used to handle the external template is selected based on the file name extension.
   *
   * @var string
   */
  public $templateUrl = '';
  /**
   * A Matisse component that will be used as this component's renderable view.
   *
   * <p>This is only set when using a Matisse view.
   *
   * @var Component|null
   */
  protected $shadowDOM = null;
  /**
   * The component's view, which renders the component's appearance.
   *
   * @var ViewInterface|null
   */
  protected $view = null;
  /**
   * The engine to be used for parsing and rendering the view if {@see $template} is set and {@see $templateUrl} is not.
   *
   * @var string
   */
  protected $viewEngineClass = MatisseEngine::class;

  /**
   * Gets the Matisse component that implements this component's renderable view (if the view is a Matisse template).
   *
   * @see provideShadowDOM()
   * @return Component|null
   */
  function getShadowDOM ()
  {
    return $this->shadowDOM;
  }

  /**
   * Sets the given Matisse component to be this component's renderable view. It also attaches it to this component.
   *
   * <p>If set, this will override {@see template} and {@see templateUrl}.
   *
   * @param Component|null $shadowDOM
   */
  function setShadowDOM (Component $shadowDOM = null)
  {
    $this->shadowDOM = $shadowDOM;
    if ($shadowDOM)
      $shadowDOM->attachTo ($this);
  }

  /**
   * @return ViewInterface|null
   */
  function getView ()
  {
    return $this->view;
  }

  /**
   * When the component's view is a matisse template, this returns the root of the parsed template, otherwise it returns
   * `null`.
   *
   * <p>Subclasses may override this to return a shadowDOM other than the component's default one.
   *
   * > <p>This is also used by {@see ComponentInspector} for inspecting a composite component's children.
   *
   * @return Component|null A {@see DocumentFragment}, but it may also be any other component type.
   */
  function provideShadowDOM ()
  {
    return $this->shadowDOM
      ?: (
      $this->view && $this->view->getEngine () instanceof MatisseEngine
        ? $this->view->getCompiled ()
        : null
      );
  }

  protected function createView ()
  {
    if (!isset($this->shadowDOM)) {
      if ($this->templateUrl) {
        $this->assertContext ();
        $this->view = $this->context->viewService->loadFromFile ($this->templateUrl);
        $this->view->compile ();
      }
      elseif ($this->template) {
        $this->assertContext ();
        $this->view = $this->context->viewService->loadFromString ($this->template, $this->viewEngineClass);
        $this->view->compile ();
      }
    }
    // Else assume the shadowDOM is already attached to this; it will be, if set via setShadowDOM().
    // Either way, generate the template.
    $this->setShadowDOM ($this->provideShadowDOM ());
  }

  /**
   * Allows subclasses to generate the view's markup dinamically.
   * If not overridden, the default behaviour is to render the view previously set by {@see createView}.
   *
   * ><p>**Note:** this returns nothing; the output is sent directly to the output buffer.
   */
  protected function render ()
  {
    if ($this->view)
      // The view model is sent to render() because the view may not be a Matisse template, in which case the model must
      // be set explicitly.
      echo $this->view->render ($this->getViewModel ());
    elseif ($this->shadowDOM)
      $this->shadowDOM->run ();
    // Otherwise, do NOT render the component's content
  }

  private function assertContext ()
  {
    if (!$this->context)
      throw new ComponentException($this,
        sprintf ("Can't render the component's template because the rendering context is not set.
<p>See <kbd>%s</kbd>", formatClassName (RenderableInterface::class)));
  }

}

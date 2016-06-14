<?php
namespace Selenia\Matisse\Traits\Component;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Interfaces\PresetsInterface;

trait RenderingTrait
{
  /**
   * Renders a set of components.
   *
   * @param Component[] $components The set of components to be rendered.
   * @return string
   */
  static function getRenderingOfSet (array $components = null)
  {
    ob_start (null, 0);
    if (isset($components))
      foreach ($components as $component)
        $component->run ();
    return ob_get_clean ();
  }

  /**
   * Renders a set of components.
   *
   * @param Component[] $components The set of components to be rendered.
   */
  static function renderSet (array $components = null)
  {
    if (isset($components))
      foreach ($components as $component)
        $component->run ();
  }

  /**
   * Runs a private child component that does not belong to the hierarchy.
   *
   * <p>**Warning:** the component will **not** be detached after begin rendered.
   *
   * @param Component $c
   */
  function attachAndRender (Component $c)
  {
    $this->attach ($c);
    $c->run ();
  }

  /**
   * Renders a set of components as if they are children of this component.
   *
   * <p>**Warning:** the components will **not** br detached after begin rendered.
   *
   * @param Component[] $components A set of external, non-attached, components.
   */
  function attachAndRenderSet (array $components)
  {
    $this->attach ($components);
    foreach ($components as $c)
      $c->run ();
  }

  /**
   * Renders a set of components as if they are children of this component.
   *
   * <p>**Warning:** the components will **not** br detached after begin rendered.
   *
   * @param Component[] $components A set of external, non-attached, components.
   * @return string
   */
  function attachSetAndGetContent (array $components)
  {
    ob_start (null, 0);
    $this->attachAndRenderSet ($components);
    return ob_get_clean ();
  }

  function getRendering ()
  {
    ob_start (null, 0);
    $this->run ();
    return ob_get_clean ();
  }

  /**
   * Performs all setup required for rendering the component.
   *
   * <p>After a call to this method, the component is ready to be rendered.
   */
  function preRun ()
  {
    $firstRendering = !$this->renderCount++;
    if ($this->isVisible ()) {
      if ($firstRendering)
        $this->setupFirstRun ();
      else $this->setupRepeatedRun ();
      $this->databind ();       // This is done on the data binding context of the component's parent.
      if ($firstRendering) {
        $this->createView ();
        $this->setupView ();
      }
      $this->afterPreRun ();   // Here, composite components may setup its view model and data binder.
    }
  }

  /**
   * Renders the component.
   *
   * <p>This performs all the setup and data binding logic required for a successful render.
   * It is the correct method that should be called for rendering, **not** {@see render}, as the later is meant for
   * subclasses to provide the actual rendering process.
   *
   * <p>**You can't override this!** But there are many extension points meant for overriding specific parts of the
   * rendering process. See the documentation to find out which suits your needs.
   *
   * @param bool $onlyContent If true, {@see render} will not be called and, instead, each child will be rendered.
   * @throws ComponentException
   */
  final function run ($onlyContent = false)
  {
    if (!$this->context)
      throw new ComponentException($this, self::ERR_NO_CONTEXT);

    $this->applyPresetsOnSelf ();

    if ($this->isVisible ()) {
      $this->preRun ();
      //---- Rendering code ----
      $this->preRender ();
      if ($onlyContent)
        $this->runChildren ();
      else $this->render ();
      $this->postRender ();
      //---- /Rendering code ----
      $this->afterRender ();
    }
  }

  /**
   * Similar to {@see getRendering}, but it returns only the component's children's rendering.
   *
   * ><p>**Note:** the component itself is not rendered.
   *
   * @return string
   */
  function runAndGetContent ()
  {
    ob_start (null, 0);
    $this->runContent ();
    return ob_get_clean ();
  }

  /**
   * Invokes doRender() recursively on the component's children (or a subset of).
   *
   * @param string|null $attrName [optional] A property name. If none, it renders all of the component's direct
   *                              children.
   *
   * @see runContent()
   */
  function runChildren ($attrName = null)
  {
    /** @var Component[] $children */
    $children = isset($attrName) ? $this->getChildren ($attrName) : $this->children;
    foreach ($children as $child)
      $child->run ();
  }

  /**
   * Similar to {@see run}, but it renders only the component's children.
   *
   * <p>You should use this instead of {@see run()} when the full rendering of the component is
   * performed by its children. If the component does some rendering itself and additionally renders its children, call
   * {@see run()} instead.
   *
   * <p>This MUST NOT be called from within a component's {@see render} method; for that purpose, use {@see runChildren}
   * instead.
   *
   * ><p>**Note:** the component itself is not rendered.<br><br>
   */
  function runContent ()
  {
    $this->run (true);
  }

  /**
   * This is an extensibility point that is called by {@see run} just before it performs its work.
   * This is only called the first time the component is run, not for subsequent repetitions.
   */
  function setupFirstRun ()
  {
    // override
  }

  /**
   * This is an extensibility point that is called by {@see run} just before it performs its work.
   * <p>This is not called the first time the component is run, it is only called for subsequent repetitions.
   */
  function setupRepeatedRun ()
  {
    // override
  }

  /**
   * Extension hook.
   */
  protected function afterPreRun ()
  {
    //override
  }

  /**
   * Called after the component is fully rendered.
   *
   * <p>Override to add debug logging, for instance.
   */
  protected function afterRender ()
  {
    //override
  }

  /**
   * Generates and/or initializes the component's view.
   *
   * <p>If not overridden, the default behaviour is to load the view from an external file, if one is defined on
   * `$templateUrl`. If not, the content of `$template` is returned, if set, otherwise no output is generated.
   *
   * <p>This is only relevant for {@see CompositeComponent} subclasses.
   */
  protected function createView ()
  {
    // override
  }

  /**
   * Do something after the component renders (ex. prepend to the output).
   */
  protected function postRender ()
  {
    //noop
  }

  /**
   * Do something before the component renders (ex. append to the output).
   */
  protected function preRender ()
  {
    //noop
  }

  /**
   * Implements the component's visual rendering code.
   * Implementation code should also call render() for each of the component's children, if any.
   *
   * <p>**DO NOT CALL DIRECTLY!**
   * <p>Use {@see run()} instead.
   *
   * > **Note:** this returns nothing; the output is sent directly to the output buffer.
   */
  protected function render ()
  {
    //implementation is specific to each component type.
  }

  /**
   * Allows a component to perform additional view-related initialization, before it is rendered.
   *
   * <p>This is specially relevant for {@see CompositeComponent} subclasses.
   * For them, this provides an opportunity to access to the compiled view generated by the parsing process.
   *
   * <p>Override to add extra initialization.
   *
   * ><p>On a {@see CompositeComponent} subclass, you may use {@see view} to access the view, or {@see shadowDOM} to
   * directly access the compiled view, if it's a Matisse view.
   */
  protected function setupView ()
  {
    // override
  }

}

<?php
namespace Selenia\Matisse\Components;

use Selenia\Matisse\Components\Base\CompositeComponent;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Exceptions\FileIOException;
use Selenia\Matisse\Properties\Base\MetadataProperties;

class IncludeProperties extends MetadataProperties
{
  /**
   * The fully qualified PHP class name of the component class to load as a child of the Include.
   *
   * <p>If {@see $view} or {@see $template} are defined, they will be set as the component's view.
   *
   * @var string
   */
  public $class = '';
  /**
   * The relative path and file name of the file to be loaded and rendered at the component's location.
   *
   * <p>Matisse will compute the final path from the root directory of the application.
   *
   * > <p>You **can** use databinding on this property, as the view is loaded at render time and the view model is
   * > available.
   *
   * @var string
   */
  public $file = '';
  /**
   * When true, the component outputs all script imports and embedded scripts for the current document.
   *
   * @var bool
   */
  public $scripts = false;
  /**
   * When true, the component outputs all CSS stylesheet imports and embedded styles for the current document.
   *
   * @var bool
   */
  public $styles = false;
  /**
   * Defines an inline template for the view.
   *
   * <p>This is usually used with a databinding expression with the `{expr|*}` syntax to insert a dynamic template
   * from a viewModel property, or with the `{#block|*}` syntax to insert a template from a content block.
   *
   * @var string
   */
  public $template = '';
  /**
   * The relative file path of the view to be loaded and rendered at the component's location.
   *
   * <p>Matisse will search for the view on all the view paths registered on the framework.
   *
   * > <p>You **can** use databinding on this property, as the view is loaded at render time and the view model is
   * > available.
   *
   * @var string
   */
  public $view = '';
}

/**
 * The **Include** component is capable of rendering content from multiple types of sources.
 *
 * <p>With it, you can:
 *
 * 1. load raw markup files;
 * - load controller-less views;
 * - load view-less components;
 * - load composite components where each component chooses its view;
 * - load composite components and define or override their view;
 * - insert managed scripts into the page;
 * - insert managed stylesheets into the page.
 * - render dynamically generated templates loaded from the viewModel or from content blocks.
 *
 * <p>One common use of Include is to assign controllers to view partials/layouts, therefore encapsulating their
 * functionality and freeing your page controller code from having to handle each and all that are included on the
 * page.
 *
 * <p>You can also define the view model of the `Include` component from markup, by specifying an attribute for each
 * model property you wish to set; the attribute name must be prefixed by `@`.
 */
class Include_ extends CompositeComponent
{
  const propertiesClass = IncludeProperties::class;

  /** @var IncludeProperties */
  public $props;

  protected function createView ()
  {
    $prop       = $this->props;
    $ctx        = $this->context;
    $controller = $prop->class;

    // Resolve controller for the view (if applicable).

    if (!exists ($controller) && exists ($prop->view))
      $controller = $ctx->findControllerForView ($prop->view);

    if (exists ($prop->template)) {
      if (exists ($controller)) {
        $subComponent           = $this->makeShadowController ($controller, $prop);
        $subComponent->template = $prop->template;
        $this->setShadowDOM ($subComponent);
      }
      else $this->template = $prop->template;
    }

    elseif (exists ($prop->view)) {
      if (exists ($controller)) {
        $subComponent              = $this->makeShadowController ($controller, $prop);
        $subComponent->templateUrl = $prop->view;
        $this->setShadowDOM ($subComponent);
      }
      else $this->templateUrl = $prop->view;
    }

    else if (exists ($prop->file)) {
      $fileContent = loadFile ($prop->file);
      if ($fileContent === false)
        throw new FileIOException($prop->file, 'read', explode (PATH_SEPARATOR, get_include_path ()));
      echo $fileContent;
      return;
    }

    else if ($prop->styles) {
      $ctx->getAssetsService ()->outputStyles ();
      return;
    }

    else if ($prop->scripts) {
      $ctx->getAssetsService ()->outputScripts ();
      return;
    }

    parent::createView ();
  }

  protected function init ()
  {
    parent::init ();
    $prop = $this->props;

    // Validate dynamic properties and rename them.

    $extra = $prop->getDynamic ();
    if ($extra) {
      foreach ($extra as $k => $v)
        if ($k[0] != '@')
          throw new ComponentException ($this, "Invalid property name: <kbd>$k</kbd>");
        else {
          $o = substr ($k, 1);
          if (isset($prop->$o))
            throw new ComponentException ($this,
              "Dynamic property <kbd>$k</kbd> conflicts with predefined property <kbd>$o</kbd>.");
          $prop->$o = $v;
          unset ($prop->$k);
        }
    }
  }

  /**
   * @param string            $controller
   * @param IncludeProperties $props
   * @return CompositeComponent
   * @throws ComponentException
   */
  protected function makeShadowController ($controller, IncludeProperties $props)
  {
    $shadowContext = $this->context->makeSubcontext ();
    $subComponent  = $shadowContext->createComponent ($controller, $this);

    if (!$subComponent instanceof CompositeComponent)
      throw new ComponentException($this,
        "Component <kbd>$controller</kbd> is not a <kbd>CompositeComponent</kbd> instance, so it can't be a controler");

    // If the controller component has its own properties, merge the Include's dynamic properties with them.
    if (isset($subComponent->props))
      $subComponent->props->apply ($props->getDynamic ());

    return $subComponent;
  }

}

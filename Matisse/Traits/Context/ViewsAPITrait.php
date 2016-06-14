<?php
namespace Selenia\Matisse\Traits\Context;

use Selenia\Interfaces\Views\ViewServiceInterface;
use Selenia\Matisse\Components;
use Selenia\Matisse\Parser\DocumentContext;

/**
 * View-related services.
 */
trait ViewsAPITrait
{
  /**
   * A mapping between modules view templates base directories and the corresponding PHP namespaces that will be
   * used for resolving view template paths to PHP controller classes.
   *
   * @var array
   */
  public $controllerNamespaces = [];
  /**
   * A map of absolute view file paths to PHP controller class names.
   *
   * <p>This is used by the `Include` component.
   *
   * @var array
   */
  public $controllers = [];
  /**
   * The view service that instantiated the current rendering engine and its associated rendering context (this
   * instance).
   *
   * @var ViewServiceInterface|null
   */
  public $viewService;

  /**
   * Attempts to find a controller class for the given view template path.
   *
   * @param string $viewName A view template absolute file path.
   * @return null|string null if no controller was found.
   */
  function findControllerForView ($viewName)
  {
    /** @var DocumentContext $this */
    $path = $this->viewService->resolveTemplatePath ($viewName, $base);
//    inspect ($viewName, $base, $path);
    if (isset($this->controllers[$path]))
      return $this->controllers[$path];

    foreach ($this->controllerNamespaces as $nsPath => $ns) {
//      inspect ($nsPath, $ns);
      if (str_beginsWith ($path, $nsPath)) {
        $remaining = substr ($path, strlen ($nsPath) + 1);
        $a         = PS ($remaining)->split ('/');
        $file      = $a->pop ();
        $nsPrefix  = $a->map ('ucfirst', false)->join ('\\')->S;
        $class     = ($p = strpos ($file, '.')) !== false
          ? ucfirst (substr ($file, 0, $p))
          : ucfirst ($file);
        $FQN       = PA ([$ns, $nsPrefix, $class])->prune ()->join ('\\')->S;
//        inspect ("CLASS $FQN");
        if (class_exists ($FQN))
          return $FQN;
      }
    }
//    inspect ("CLASS NOT FOUND FOR VIEW $viewName");
    return null;
  }

}

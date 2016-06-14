<?php
namespace Selenia\Plugins\Matisse\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Exceptions\Fatal\FileNotFoundException;
use Selenia\Interfaces\DI\InjectorInterface;
use Selenia\Interfaces\Http\RequestHandlerInterface;
use Selenia\Plugins\Matisse\Components\Base\PageComponent;
use Selenia\Plugins\Matisse\Parser\DocumentContext;

/**
 * It allows a designer to rapidly prototype the application by automatically providing routing for URLs matching files
 * on the views directories, which will be routed to a generic controller that will load the matched view.
 *
 * <p>**Note:** currently, this middleware only supports Matisse templates.
 *
 * <p>**This is NOT recommended for production!**
 *
 * <p>You should register this middleware right before the router, but only if `debugMode = false`.
 */
class AutoRoutingMiddleware implements RequestHandlerInterface
{
  /**
   * @var DocumentContext
   */
  private $context;
  /**
   * @var InjectorInterface
   */
  private $injector;

  public function __construct (InjectorInterface $injector, DocumentContext $context)
  {
    $this->injector = $injector;
    $this->context  = $context;
  }

  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $URL = $request->getAttribute ('virtualUri');
    if ($URL == '') $URL = 'index';
    elseif (substr ($URL, -1) == '/') $URL = $URL . 'index';

    /** @var \Selenia\Plugins\Matisse\Components\Base\PageComponent $page */
    $page = $this->injector->make (PageComponent::class);
    $page->setup (null, $this->context); // Here we assume the templating engine is always Matisse.
    $page->templateUrl = "$URL.html";

    try {
      return $page ($request, $response, $next);
    }
    catch (FileNotFoundException $e) {
      return $next ();
    }
  }

}

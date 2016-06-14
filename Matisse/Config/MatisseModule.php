<?php
namespace Selenia\Matisse\Config;

use Selenia\Application;
use Selenia\DefaultFilters;
use Selenia\Interfaces\DI\InjectorInterface;
use Selenia\Interfaces\DI\ServiceProviderInterface;
use Selenia\Interfaces\ModuleInterface;
use Selenia\Interfaces\Views\ViewServiceInterface;
use Selenia\Matisse\Interfaces\DataBinderInterface;
use Selenia\Matisse\Lib\DataBinder;
use Selenia\Matisse\Lib\FilterHandler;
use Selenia\Matisse\Parser\DocumentContext;
use Selenia\Matisse\Services\MacrosService;

class MatisseModule implements ServiceProviderInterface, ModuleInterface
{
  /** @var bool */
  private $debugMode;

  function boot (Application $app, $debugMode)
  {
    $app->condenseLiterals = !$debugMode;
    $app->compressOutput   = !$debugMode;
    $this->debugMode       = $debugMode;
  }

  function register (InjectorInterface $injector)
  {
    $injector
      ->prepare (DocumentContext::class,
        function (DocumentContext $ctx, InjectorInterface $injector) {
          $app         = $injector->make (Application::class);
          $viewService = $injector->make (ViewServiceInterface::class);
          $ctx->registerTags ($app->tags);
          $ctx->setFilterHandler ($filterHandler = new FilterHandler);
          $filterHandler->registerFilters (new DefaultFilters ($app));
          $ctx->condenseLiterals     = $app->condenseLiterals;
          $ctx->debugMode            = $this->debugMode;
          $ctx->controllers          = $app->controllers;
          $ctx->controllerNamespaces = $app->controllerNamespaces;
          $ctx->presets              = map ($app->presets,
            function ($class) use ($app) { return $app->injector->make ($class); });
          $ctx->injector             = $injector;
          $ctx->viewService          = $viewService;
          $ctx->getDataBinder ()->setContext ($ctx);
          return $ctx;
        })
      ->share (DocumentContext::class)
      ->prepare (MacrosService::class, function (MacrosService $macrosService, InjectorInterface $injector) {
        $app                              = $injector->make (Application::class);
        $macrosService->macrosDirectories = $app->macrosDirectories;
        $macrosService->macrosExt         = '.html';
      })
      ->share (MacrosService::class)
      ->alias (DataBinderInterface::class, DataBinder::class);
  }

}

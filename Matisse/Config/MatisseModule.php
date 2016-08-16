<?php
namespace Electro\Plugins\Matisse\Config;

use Electro\Application;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\DI\ServiceProviderInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Interfaces\Views\ViewServiceInterface;
use Electro\Plugins\Matisse\Interfaces\DataBinderInterface;
use Electro\Plugins\Matisse\Lib\DataBinder;
use Electro\Plugins\Matisse\Lib\DefaultFilters;
use Electro\Plugins\Matisse\Lib\FilterHandler;
use Electro\Plugins\Matisse\Lib\MatisseEngine;
use Electro\Plugins\Matisse\Parser\DocumentContext;
use Electro\Plugins\Matisse\Services\MacrosService;

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
          /** @var Application $app */
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
      ->alias (DataBinderInterface::class, DataBinder::class)
      ->prepare (ViewServiceInterface::class, function (ViewServiceInterface $viewService) {
        $viewService->register (MatisseEngine::class, '/\.html$/');
      });;
  }

}

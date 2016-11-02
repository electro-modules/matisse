<?php
namespace Electro\Plugins\Matisse\Config;

use Electro\Core\Assembly\ModuleInfo;
use Electro\Core\Assembly\Services\Bootstrapper;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Interfaces\Views\ViewServiceInterface;
use Electro\Plugins\Matisse\Interfaces\DataBinderInterface;
use Electro\Plugins\Matisse\Lib\DataBinder;
use Electro\Plugins\Matisse\Lib\MatisseEngine;
use Electro\Plugins\Matisse\Parser\DocumentContext;
use Electro\Plugins\Matisse\Services\MacrosService;
use const Electro\Core\Assembly\Services\REGISTER_SERVICES;

class MatisseModule implements ModuleInterface
{
  static function bootUp (Bootstrapper $bootstrapper, ModuleInfo $moduleInfo)
  {
    $bootstrapper->on (REGISTER_SERVICES, function (InjectorInterface $injector) {
      $injector
        ->share (DocumentContext::class)
        ->share (MacrosService::class)
        ->share (MatisseSettings::class)
        ->alias (DataBinderInterface::class, DataBinder::class)
        ->prepare (ViewServiceInterface::class, function (ViewServiceInterface $viewService) {
          $viewService->register (MatisseEngine::class, '/\.html$/');
        });
    });
  }

}

<?php
namespace Electro\Plugins\Matisse\Config;

use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\KernelInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Interfaces\Views\ViewServiceInterface;
use Electro\Kernel\Lib\ModuleInfo;
use Electro\Plugins\Matisse\Interfaces\DataBinderInterface;
use Electro\Plugins\Matisse\Lib\DataBinder;
use Electro\Plugins\Matisse\Lib\MatisseEngine;
use Electro\Plugins\Matisse\Parser\DocumentContext;
use Electro\Plugins\Matisse\Services\MacrosService;
use Electro\Profiles\WebProfile;

class MatisseModule implements ModuleInterface
{
  static function getCompatibleProfiles ()
  {
    return [WebProfile::class];
  }

  static function startUp (KernelInterface $kernel, ModuleInfo $moduleInfo)
  {
    $kernel->onRegisterServices (
      function (InjectorInterface $injector) {
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

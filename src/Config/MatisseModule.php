<?php

namespace Matisse\Config;

use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\KernelInterface;
use Electro\Interfaces\ModuleInterface;
use Electro\Interfaces\Views\ViewServiceInterface;
use Electro\Kernel\Lib\ModuleInfo;
use Electro\Profiles\WebProfile;
use Matisse\Interfaces\DataBinderInterface;
use Matisse\Lib\DataBinder;
use Matisse\Lib\MacroPropertiesCache;
use Matisse\Lib\MatisseEngine;
use Matisse\Parser\DocumentContext;
use Matisse\Services\MacrosService;

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
          })
          ->share (MacroPropertiesCache::class);
      });
  }

}

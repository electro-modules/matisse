<?php

namespace Matisse\Lib;

use Electro\Caching\Config\CachingSettings;
use Electro\Caching\Drivers\GeneratedCodeCache;
use Electro\Interfaces\KernelInterface;
use Electro\Kernel\Config\KernelSettings;
use Electro\ViewEngine\Config\ViewEngineSettings;
use Psr\Log\LoggerInterface;

class MacroPropertiesCache extends GeneratedCodeCache
{
  public function __construct (KernelSettings $kernelSettings, CachingSettings $cachingSettings,
                               LoggerInterface $logger, ViewEngineSettings $viewEngineSettings, KernelInterface $kernel)
  {
    parent::__construct ($kernelSettings, $cachingSettings, $logger);
    $this->setNamespace ('views/macros/props/' . $kernel->getProfile ()->getName ());
    $this->enabled = $viewEngineSettings->caching ();
  }

}

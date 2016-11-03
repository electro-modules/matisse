<?php
namespace Electro\Plugins\Matisse\Config;

use Electro\Kernel\Config\KernelSettings;
use Electro\Kernel\Lib\ModuleInfo;
use Electro\Plugins\Matisse\Lib\DefaultFilters;
use Electro\Plugins\Matisse\Lib\FilterHandler;
use Electro\Plugins\Matisse\Parser\DocumentContext;
use Electro\Plugins\Matisse\Services\MacrosService;
use Electro\Traits\ConfigurationTrait;
use Electro\ViewEngine\Config\ViewEngineSettings;
use Electro\ViewEngine\Services\AssetsService;
use PhpKit\Flow\FilesystemFlow;

/**
 * Configuration settings for the Matisse rendering engine.
 *
 * @method $this|bool debugMode (bool $v = null)
 * @method $this|string moduleMacrosPath (string $v = null) The relative path of the macros folder inside a module
 */
class MatisseSettings
{
  use ConfigurationTrait;

  /**
   * @var KernelSettings
   */
  private $kernelSettings;
  /**
   * @var AssetsService
   */
  private $assetsService;
  /**
   * A mapping between modules view templates base directories and the corresponding PHP namespaces that will be
   * used for resolving view template paths to PHP controller classes.
   *
   * @var array
   */
  private $controllerNamespaces = [];
  /**
   * A map of absolute view file paths to PHP controller class names.
   *
   * <p>This is used by the `Include` component.
   *
   * @var array
   */
  private $controllers = [];
  /**
   * This is automatically initialized from the environment DEBUG setting.
   *
   * @var bool
   */
  private $debugMode = false;
  /**
   * @var MacrosService
   */
  private $macrosService;
  /**
   * @var string
   */
  private $moduleMacrosPath = 'resources/macros';
  /**
   * @var string[] A list of "preset" class names.
   */
  private $presets = [];
  /**
   * Registered Matisse tags.
   *
   * @var array
   */
  private $tags = [];
  /**
   * @var ViewEngineSettings
   */
  private $viewEngineSettings;

  public function __construct (KernelSettings $kernelSettings, MacrosService $macrosService, AssetsService $assetsService,
                               ViewEngineSettings $viewEngineSettings, $debugMode)
  {
    $this->kernelSettings     = $kernelSettings;
    $this->macrosService      = $macrosService;
    $this->assetsService      = $assetsService;
    $this->debugMode          = $debugMode;
    $this->viewEngineSettings = $viewEngineSettings;
  }

  /**
   * Returns all currently registered presets class names.
   *
   * @return \string[]
   */
  function getPresets ()
  {
    return $this->presets;
  }

  /**
   * Configures a DocumentContext from Matisse's configuration settings.
   *
   * @param DocumentContext $ctx
   */
  function initContext (DocumentContext $ctx)
  {
    $ctx->condenseLiterals     = !$this->debugMode;
    $ctx->debugMode            = $this->debugMode;
    $ctx->controllers          = $this->controllers;
    $ctx->controllerNamespaces = $this->controllerNamespaces;
    $ctx->registerTags ($this->tags);
    $ctx->setFilterHandler (new FilterHandler (new DefaultFilters));
    $ctx->getDataBinder ()->setContext ($ctx);
  }

  /**
   * A list of relative file paths of assets published by the module, relative to the module's public folder.
   *
   * <p>Registered assets will be automatically loaded by Matisse-rendered pages.
   * <p>Also, if they are located on a sub-directory of `/resources` , the framework's build process may automatically
   * concatenate and minify them for a release-grade build.
   *
   * > <p>**Important:** make sure to call {@see publishPublicDirAs()} before calling this method.
   *
   * @param ModuleInfo $moduleInfo
   * @param string[]   $assets
   * @return $this
   */
  function registerAssets (ModuleInfo $moduleInfo, $assets)
  {
    $publicUrl = "{$this->kernelSettings->modulesPublishingPath}/$moduleInfo->name";
    // TODO: handle assets on a sub-directory of resources.
    foreach ($assets as $path) {
      $path = "$publicUrl/$path";
      $p    = strrpos ($path, '.');
      if (!$p) continue;
      $ext = substr ($path, $p + 1);
      switch ($ext) {
        case 'css':
          $this->assetsService->addStylesheet ($path);
          break;
        case 'js':
          $this->assetsService->addScript ($path);
          break;
      }
    }
    return $this;
  }

  /**
   * @param array $map Map of tag names to component classes.
   * @return $this
   */
  function registerComponents (array $map)
  {
    array_mergeInto ($this->tags, $map);
    return $this;
  }

  /**
   * Registers a map of relative view file paths to PHP controller class names.
   *
   * <p>The array keys are file paths which, by default, are relative to the current module's base directory.
   *
   * @param ModuleInfo $moduleInfo
   * @param array      $mappings
   * @return $this
   */
  function registerControllers (ModuleInfo $moduleInfo, array $mappings)
  {
    $ctr =& $this->controllers;
    foreach ($mappings as $path => $class) {
      $path       = "$moduleInfo->path/{$this->viewEngineSettings->moduleViewsPath()}/$path";
      $ctr[$path] = $class;
    }
    return $this;
  }

  /**
   * Registers a mapping between the given PHP namespace and the module's view templates base directory that will be
   * used for resolving view template paths to PHP controller classes.
   *
   * @param ModuleInfo $moduleInfo
   * @param string     $namespace
   * @param string     $basePath [optional] A base path for mapping, relative to the module's view templates directory.
   * @return $this
   */
  function registerControllersNamespace (ModuleInfo $moduleInfo, $namespace, $basePath = '')
  {
    if ($basePath)
      $basePath = "/$basePath";
    $this->controllerNamespaces ["$moduleInfo->path/{$this->viewEngineSettings->moduleViewsPath()}$basePath"] =
      $namespace;
    return $this;
  }

  /**
   * Registers a module's macros directory, along with any immediate sub-directories.
   *
   * @param ModuleInfo $moduleInfo
   * @return $this
   */
  function registerMacros (ModuleInfo $moduleInfo)
  {
    $path = "{$this->kernelSettings->baseDirectory}/$moduleInfo->path/{$this->moduleMacrosPath}";
    if (fileExists ($path)) {
      $all = FilesystemFlow::from ($path)->onlyDirectories ()->keys ()->all ();
      array_unshift ($all, $path);
      $this->macrosService->macrosDirectories = array_merge ($all, $this->macrosService->macrosDirectories);
    }
    return $this;
  }

  /**
   * @param string[] $classes List of class names providing component presets.
   * @return $this
   */
  function registerPresets (array $classes)
  {
    array_mergeInto ($this->presets, $classes);
    return $this;
  }

}

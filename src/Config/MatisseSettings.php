<?php

namespace Matisse\Config;

use Electro\Kernel\Config\KernelSettings;
use Electro\Kernel\Lib\ModuleInfo;
use Electro\Traits\ConfigurationTrait;
use Electro\ViewEngine\Config\ViewEngineSettings;
use Matisse\Lib\DefaultFilters;
use Matisse\Lib\FilterHandler;
use Matisse\Parser\DocumentContext;
use PhpKit\Flow\FilesystemFlow;

/**
 * Configuration settings for the Matisse rendering engine.
 *
 * @method $this|bool collapseWhitespace (bool $v = null) Enable to remove whitespace around raw markup blocks
 * @method $this|bool inspectDOM (bool $v = null) Enable to inspect the server-side view-DOM on a console panel
 * @method $this|bool devEnv (bool $v = null) When TRUE, whitespace between tags is not removed
 * @method $this|string moduleMacrosPath (string $v = null) The path of the macros folder relative to the views path
 * @method $this|string macrosExt (string $v = null) File extension of macro files
 */
class MatisseSettings
{
  use ConfigurationTrait;

  /**
   * Enable to remove whitespace around raw markup blocks.
   *
   * @var bool
   */
  private $collapseWhitespace = false;
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
   * @var KernelSettings
   */
  private $kernelSettings;
  /**
   * @var string[]
   */
  private $macrosDirectories = [];
  /**
   * File extension of macro files.
   *
   * @var string
   */
  private $macrosExt = '.html';
  /**
   * @var string
   */
  private $moduleMacrosPath = 'macros';
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

  public function __construct (KernelSettings $kernelSettings, ViewEngineSettings $viewEngineSettings)
  {
    $this->kernelSettings     = $kernelSettings;
    $this->viewEngineSettings = $viewEngineSettings;
  }

  /**
   * Directories where macros can be found.
   * <p>They will be search in order until the requested macro is found.
   * <p>These paths will be registered on the templating engine.
   * <p>This is preinitialized to the application macro's path.
   *
   * @return string[]
   */
  function getMacrosDirectories ()
  {
    return $this->macrosDirectories;
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
    $ctx->condenseLiterals     = $this->collapseWhitespace;
    $ctx->controllers          = $this->controllers;
    $ctx->controllerNamespaces = $this->controllerNamespaces;
    $ctx->registerTags ($this->tags);
    $ctx->setFilterHandler (new FilterHandler (new DefaultFilters));
    $ctx->getDataBinder ()->setContext ($ctx);
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
   * <p>The array keys are file paths which, by default, are relative to the current module's views directory.
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
    $path =
      "{$this->kernelSettings->baseDirectory}/$moduleInfo->path/{$this->viewEngineSettings->moduleViewsPath()}/$this->moduleMacrosPath";
    if (fileExists ($path)) {
      $all = FilesystemFlow::from ($path)
                           ->onlyDirectories ()
                           ->map (function (\SplFileInfo $info, &$k) {
                             $k = $info->getPathname ();
                             return "$this->moduleMacrosPath/" . $info->getFilename ();
                           })->all ();
//      dump ($all);
      $all = array_merge ([$path => $this->moduleMacrosPath], $all);
      $this->macrosDirectories = array_merge ($all, $this->macrosDirectories);
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

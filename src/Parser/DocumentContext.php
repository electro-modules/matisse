<?php

namespace Matisse\Parser;

use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\Views\ViewServiceInterface;
use Electro\Traits\InspectionTrait;
use Electro\ViewEngine\Services\AssetsService;
use Electro\ViewEngine\Services\BlocksService;
use Matisse\Components\Base\Component;
use Matisse\Components\Fetchable;
use Matisse\Config\MatisseSettings;
use Matisse\Interfaces\DataBinderInterface;
use Matisse\Interfaces\PresetsInterface;
use Matisse\Services\MacrosService;
use Matisse\Traits\Context\ComponentsAPITrait;
use Matisse\Traits\Context\FiltersAPITrait;
use Matisse\Traits\Context\ViewsAPITrait;

/**
 * A Matisse rendering context.
 *
 * <p>The context holds state and configuration information shared between all components on a document.
 * It also conveniently provides APIs for accessing/managing Assets, Blocks, etc.
 *
 * ><p>**Warning:** do NOT clone instances of this class; call {@see makeSubcontext} instead, otherwise undesired
 * side-effects will happen (ex. presets may fail).
 */
class DocumentContext
{
  use InspectionTrait;
  use ComponentsAPITrait;
  use FiltersAPITrait;
  use ViewsAPITrait;

  const FORM_ID = 'selenia-form';

  static $INSPECTABLE = [
    'condenseLiterals',
    'controllerNamespaces',
    'controllers',
    'presets',
    'dataBinder',
    'viewService',
    'assetsService',
    'blocksService',
    'macrosService',
  ];

  /**
   * Remove white space around raw markup blocks.
   *
   * @var bool
   */
  public $condenseLiterals = false;
  /**
   * The injector allows the creation of components with yet unknown dependencies.
   *
   * @var InjectorInterface
   */
  public $injector;
  /**
   * @var MatisseSettings
   */
  public $matisseSettings;
  /**
   * A stack of presets.
   *
   * <p>Each preset is an instance of a class where methods are named with component class names.
   * <p>When components are being instantiated, if they match a class name on any of the stacked presets,
   * they will be passed to the corresponding methods for additional initialization.
   * <p>Callbacks also receive a nullable array argument with the properties being applied.
   *
   * @var PresetsInterface[]|object[]
   */
  public $presets = [];
  /**
   * @var Fetchable|null The root component for rendering responses to Fetch requests.
   */
  public $fetchableRoot = null;
  /**
   * @var AssetsService
   */
  private $assetsService;
  /**
   * @var \Electro\ViewEngine\Services\BlocksService
   */
  private $blocksService;
  /**
   * The document's data binder.
   *
   * @var DataBinderInterface
   */
  private $dataBinder;
  /**
   * @var MacrosService
   */
  private $macrosService;

  /**
   * DocumentContext constructor.
   *
   * @param AssetsService        $assetsService
   * @param BlocksService        $blocksService
   * @param MacrosService        $macrosService
   * @param DataBinderInterface  $dataBinder
   * @param InjectorInterface    $injector
   * @param MatisseSettings      $matisseSettings
   * @param ViewServiceInterface $viewService
   */
  function __construct (AssetsService $assetsService, BlocksService $blocksService, MacrosService $macrosService,
                        DataBinderInterface $dataBinder, InjectorInterface $injector, MatisseSettings $matisseSettings,
                        ViewServiceInterface $viewService)
  {
    $this->tags            = self::$coreTags;
    $this->dataBinder      = $dataBinder;
    $this->assetsService   = $assetsService;
    $this->blocksService   = $blocksService;
    $this->macrosService   = $macrosService;
    $this->injector        = $injector;
    $this->viewService     = $viewService;
    $this->matisseSettings = $matisseSettings;
    $this->presets         = map ($matisseSettings->getPresets (), function ($class) {
      return $this->injector->make ($class);
    });
    $matisseSettings->initContext ($this);
  }

  /**
   * Sets main form's `enctype` to `multipart/form-data`, allowing file upload fields.
   *
   * > <p>This can be called multiple times.
   */
  public function enableFileUpload ()
  {
    $FORM_ID = self::FORM_ID;
    $this->assetsService->addInlineScript ("$('#$FORM_ID').attr('enctype','multipart/form-data');", 'setEncType');
  }

  /**
   * @return AssetsService
   */
  public function getAssetsService ()
  {
    return $this->assetsService;
  }

  /**
   * @return \Electro\ViewEngine\Services\BlocksService
   */
  public function getBlocksService ()
  {
    return $this->blocksService;
  }

  /**
   * Gets the document's data binder.
   *
   * @return DataBinderInterface
   */
  public function getDataBinder ()
  {
    return $this->dataBinder;
  }

  /**
   * @return MacrosService
   */
  public function getMacrosService ()
  {
    return $this->macrosService;
  }

  public function makeSubcontext ()
  {
    $sub = clone $this;
    // Sub-contexts inherit the parent's presets (without this, the Presets component will not work correctly)
    $sub->presets =& $this->presets;

    $sub->dataBinder = $this->dataBinder->makeNew ();
    $sub->dataBinder->setContext ($sub);
    return $sub;
  }

}

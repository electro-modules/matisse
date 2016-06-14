<?php
namespace Selenia\Matisse\Parser;

use Selenia\Interfaces\DI\InjectorInterface;
use Selenia\Matisse\Interfaces\DataBinderInterface;
use Selenia\Matisse\Interfaces\PresetsInterface;
use Selenia\Matisse\Services\MacrosService;
use Selenia\Matisse\Traits\Context\ComponentsAPITrait;
use Selenia\Matisse\Traits\Context\FiltersAPITrait;
use Selenia\Matisse\Traits\Context\ViewsAPITrait;
use Selenia\Traits\InspectionTrait;
use Selenia\ViewEngine\Services\AssetsService;
use Selenia\ViewEngine\Services\BlocksService;

/**
 * A Matisse rendering context.
 *
 * <p>The context holds state and configuration information shared between all components on a document.
 * It also conveniently provides APIs for accessing/managing Assets, Blocks, etc.
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
    'debugMode',
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
   * Set to true to generate pretty-printed markup.
   *
   * @var bool
   */
  public $debugMode = false;
  /**
   * The injector allows the creation of components with yet unknown dependencies.
   *
   * @var InjectorInterface
   */
  public $injector;
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
   * @var AssetsService
   */
  private $assetsService;
  /**
   * @var \Selenia\ViewEngine\Services\BlocksService
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
   * @param AssetsService       $assetsService
   * @param BlocksService       $blocksService
   * @param MacrosService       $macrosService
   * @param DataBinderInterface $dataBinder
   */
  function __construct (AssetsService $assetsService, BlocksService $blocksService, MacrosService $macrosService,
                        DataBinderInterface $dataBinder)
  {
    $this->tags          = self::$coreTags;
    $this->dataBinder    = $dataBinder;
    $this->assetsService = $assetsService;
    $this->blocksService = $blocksService;
    $this->macrosService = $macrosService;
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
   * @return \Selenia\ViewEngine\Services\BlocksService
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
    // Sub-contexts inherit the parent's presets (without this, the Apply component will not work)
    $sub->presets    =& $this->presets;

    $sub->dataBinder = $this->dataBinder->makeNew ();
    $sub->dataBinder->setContext ($sub);
    return $sub;
  }

}

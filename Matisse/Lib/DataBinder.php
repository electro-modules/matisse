<?php
namespace Selenia\Matisse\Lib;

use PhpKit\WebConsole\Lib\Debug;
use Psr\Http\Message\ServerRequestInterface;
use Selenia\Application;
use Selenia\Interfaces\CustomInspectionInterface;
use Selenia\Interfaces\Navigation\NavigationInterface;
use Selenia\Interfaces\Navigation\NavigationLinkInterface;
use Selenia\Interfaces\RenderableInterface;
use Selenia\Interfaces\SessionInterface;
use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Interfaces\DataBinderInterface;
use Selenia\Matisse\Parser\DocumentContext;
use Selenia\Matisse\Properties\Base\AbstractProperties;
use Selenia\ViewEngine\Lib\ViewModel;

/**
 * Manages the view's data-binding context.
 *
 * <p>Instances of this class are immutable.
 */
class DataBinder implements DataBinderInterface, CustomInspectionInterface
{
  /**
   * @var DocumentContext|null If null, the binder cannot render content blocks.
   */
  private $context = null;
  /**
   * @var AbstractProperties|null
   */
  private $props;
  /**
   * @var ViewModel
   */
  private $viewModel;

  /**
   * @param ViewModel|null          $viewModel [optional] If not set, a new, blank view model will be assigned.
   * @param AbstractProperties|null $props     [optional] If not set, no properties will be available.
   */
  public function __construct (ViewModel $viewModel = null, AbstractProperties $props = null)
  {
    $this->viewModel = $viewModel ?: new ViewModel;
    $this->props     = $props;
  }

  function filter ($name, ...$args)
  {
    $filter = $this->context->getFilter ($name);
    return call_user_func_array ($filter, $args);
  }

  function get ($key)
  {
    return $this->viewModel->$key;
  }

  function getProps ()
  {
    return $this->props;
  }

  function setProps (AbstractProperties $props = null)
  {
    $this->props = $props;
  }

  function getViewModel ()
  {
    return $this->viewModel;
  }

  function setViewModel ($viewModel)
  {
    $this->viewModel = $viewModel;
  }

  function inspect ()
  {
    $VMFilter = function ($k, $v, $o) {
      if ($v instanceof Application ||
          $v instanceof NavigationInterface ||
          $v instanceof NavigationLinkInterface ||
          $v instanceof SessionInterface ||
          $v instanceof ServerRequestInterface ||
          $v instanceof DocumentContext ||
          $v instanceof Component
      ) return '...';
      return true;
    };

    return _log ()->getTable ([
      Debug::getType ($this->viewModel) => Debug::RAW_TEXT .
                                           _log ()->getTable ($this->viewModel, '', true, true, 2, $VMFilter),
      is_null ($this->props) ? 'Properties'
        : Debug::getType ($this->props) => Debug::RAW_TEXT .
                                           _log ()->getTable ($this->props, '', true, true, 1,
                                             ['props', 'component', 'hidden']),
    ], Debug::getType ($this));
  }

  function makeNew ()
  {
    return new static;
  }

  function prop ($key)
  {
    if (!$this->props) return null;
    $v = $this->props->getComputed ($key);
    if ($v && $v instanceof RenderableInterface)
      return $v->getRendering ();
    return $v;
  }

  function renderBlock ($name)
  {
    return $this->context->getBlocksService ()->getBlock ($name)->render ();
  }

  function setContext (DocumentContext $context)
  {
    $this->context = $context;
  }
}

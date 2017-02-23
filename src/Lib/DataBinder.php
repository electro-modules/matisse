<?php

namespace Matisse\Lib;

use Electro\Interfaces\CustomInspectionInterface;
use Electro\Interfaces\Navigation\NavigationInterface;
use Electro\Interfaces\Navigation\NavigationLinkInterface;
use Electro\Interfaces\RenderableInterface;
use Electro\Interfaces\SessionInterface;
use Electro\Interop\ViewModel;
use Electro\Kernel\Config\KernelSettings;
use Matisse\Components\Base\Component;
use Matisse\Interfaces\DataBinderInterface;
use Matisse\Parser\DocumentContext;
use Matisse\Properties\Base\AbstractProperties;
use PhpKit\WebConsole\Lib\Debug;
use Psr\Http\Message\ServerRequestInterface;

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
      if ($v instanceof KernelSettings ||
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

  public function offsetExists ($offset)
  {
    return isset ($this->viewModel[$offset]) || method_exists ($this->viewModel, $offset) || $offset == 'this';
  }

  public function offsetGet ($offset)
  {
    if (isset($offset)) {
      $vm = $this->viewModel;
      if (isset ($vm[$offset]))
        return $vm[$offset];
      if (method_exists ($vm, $offset))
        return $vm->$offset ();
      if ($offset == 'this')
        return $vm;
    }
    return null;
  }

  public function offsetSet ($offset, $value)
  {
    $this->viewModel[$offset] = $value;
  }

  public function offsetUnset ($offset)
  {
    unset ($this->viewModel[$offset]);
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

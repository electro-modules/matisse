<?php
namespace Selenia\Matisse\Interfaces;

use Selenia\Matisse\Exceptions\DataBindingException;
use Selenia\Matisse\Exceptions\FilterHandlerNotFoundException;
use Selenia\Matisse\Parser\DocumentContext;
use Selenia\Matisse\Properties\Base\AbstractProperties;
use Selenia\ViewEngine\Lib\ViewModel;

/**
 * Provides a data context for evaluating expressions and methods to manage that context.
 */
interface DataBinderInterface
{
  /**
   * Executes a filter with the given arguments.
   *
   * @param string $name    Filter name.
   * @param array  ...$args Filter arguments. The first argument is always the filter's implicit argument.
   * @return mixed
   * @throws FilterHandlerNotFoundException if the filter is not found.
   */
  function filter ($name, ...$args);

  /**
   * Gets a value with the given name from the view model.
   *
   * @param string $key
   * @return mixed null if not found.
   * @throws DataBindingException
   */
  function get ($key);

  /**
   * Gets the bound component properties.
   *
   * @return $this|null|AbstractProperties
   */
  function getProps ();

  /**
   * Gets the binder's view model.
   *
   * @return ViewModel
   */
  function getViewModel ();

  /**
   * Returns a new binder instance of the same class.
   *
   * @return DataBinderInterface
   */
  function makeNew ();

  /**
   * Gets a value with the given name from the bound component properties, performing data binding as needed.
   *
   * @param string $key
   * @return mixed null if not found.
   * @throws DataBindingException
   */
  function prop ($key);

  /**
   * Renders a content block for a {#block} reference on an expression.
   *
   * @param string $name The block name.
   * @return string The rendered markup.
   */
  function renderBlock ($name);

  /**
   * Sets the document context for the binder. This is used to access the blocks service.
   *
   * @param DocumentContext $context
   */
  function setContext (DocumentContext $context);

  /**
   * Assigns properties to the binder.
   *
   * @param AbstractProperties|null $props
   */
  function setProps (AbstractProperties $props = null);

  /**
   * Assigns a view model to the binder.
   *
   * @param ViewModel $viewModel
   */
  function setViewModel ($viewModel);

}

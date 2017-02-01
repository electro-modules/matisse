<?php

namespace Matisse\Interfaces;

use Electro\Interfaces\Views\ViewModelInterface;
use Electro\Interop\ViewModel;
use Matisse\Exceptions\FilterHandlerNotFoundException;
use Matisse\Parser\DocumentContext;
use Matisse\Properties\Base\AbstractProperties;

/**
 * Provides a data context for evaluating expressions and methods to manage that context.
 */
interface DataBinderInterface extends \ArrayAccess
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
   * Assigns a view model to the binder.
   *
   * @param ViewModelInterface $viewModel
   */
  function setViewModel ($viewModel);

}

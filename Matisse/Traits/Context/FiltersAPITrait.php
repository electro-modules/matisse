<?php
namespace Selenia\Matisse\Traits\Context;

use Selenia\Matisse\Components;
use Selenia\Matisse\Exceptions\FilterHandlerNotFoundException;

/**
 * Manages Matisse rendering filters.
 */
trait FiltersAPITrait
{
  /**
   * A class instance who's methods provide filter implementations.
   *
   * The handler can be an instance of a proxy class, which dynamically resolves the filter invocations trough a
   * `__call` method.
   *
   * > Filter Handlers should throw an exception if a handler method is not found.
   *
   * > <p>An handler implementation is available on the {@see FilterHandler} class.
   *
   * @var object
   */
  private $filterHandler;

  /**
   * @param string $name
   * @return callable A function that implements the filter.
   * @throws FilterHandlerNotFoundException if the filter is not found or if no filter handler is set.
   */
  function getFilter ($name)
  {
    if (!isset($this->filterHandler))
      throw new FilterHandlerNotFoundException ("Can't use filters if no filter handler is set.");
    $handler = [$this->filterHandler, $name];
    if (is_callable ($handler))
      return $handler;
    throw new FilterHandlerNotFoundException ("Filter <kbd>$name</kbd> was not found.");
  }

  /**
   * @return object
   */
  function getFilterHandler ()
  {
    return $this->filterHandler;
  }

  /**
   * @param object $filterHandler
   */
  function setFilterHandler ($filterHandler)
  {
    $this->filterHandler = $filterHandler;
  }

}

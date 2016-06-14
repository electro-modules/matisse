<?php
namespace Selenia\Matisse\Lib;

use Selenia\Matisse\Exceptions\FilterHandlerNotFoundException;
use Selenia\Traits\InspectionTrait;

/**
 * Resolves filter invocations to filter implementations,
 *
 * The registered filters will be available for use on databinding expressions when rendering.
 */
class FilterHandler
{
  use InspectionTrait;

  static $INSPECTABLE = ['filters'];
  /**
   * If not match for a filter is found on the map of registered filters, a call will be made on the fallback handler
   * object to a method named `'filter_' . $filterName`.
   *
   * > The handler must provide concrete methods for the filters; dynamic (magic) invocations are not supported.
   *
   * @var object
   */
  private $fallbackHandler;
  /**
   * Map of filter names to filter implementation functions.
   *
   * Filters can be used on databinding expressions. Ex: {!a.c|myFilter}
   *
   * @var array
   */
  private $filters = [];

  function __call ($name, $args)
  {
    $method = "filter_$name";
    if (isset($this->filters[$method]))
      return call_user_func_array ($this->filters[$method], $args);

    if (isset($this->fallbackHandler)) {
      if (method_exists ($this->fallbackHandler, $method))
        return call_user_func_array ([$this->fallbackHandler, $method], $args);
    }
    throw new FilterHandlerNotFoundException(sprintf ("<p><p>Handler method: <kbd>%s</kbd><p>Arguments: <kbd>%s</kbd>",
      $method, var_export ($args, true)));
  }

  function registerFallbackHandler ($handler)
  {
    $this->fallbackHandler = $handler;
  }

  /**
   * Register a set of filters for use on databinding expressions when rendering.
   *
   * @param array|object $filters Either a map of filter names to filter implementation functions or an instance of a
   *                              class where each public method (except the constructor) is a named filter function.
   */
  function registerFilters ($filters)
  {
    if (is_object ($filters)) {
      $keys    = array_diff (get_class_methods ($filters), ['__construct']);
      $values  = array_map (function ($v) use ($filters) { return [$filters, $v]; }, $keys);
      $filters = array_combine ($keys, $values);
    };
    $this->filters = array_merge ($this->filters, $filters);
  }

}

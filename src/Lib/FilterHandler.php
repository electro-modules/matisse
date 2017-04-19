<?php
namespace Matisse\Lib;

use Electro\Traits\InspectionTrait;
use Matisse\Exceptions\FilterHandlerNotFoundException;
use PhpKit\WebConsole\Lib\Debug;

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

  /**
   * Register a set of filters for use on databinding expressions when rendering.
   *
   * @param array|object|null $filters Either a map of filter names to filter implementation functions or an instance
   *                                   of a class where each public method (except the constructor) is a named filter
   *                                   function.
   */
  function __construct ($filters = null)
  {
    if ($filters)
      $this->set ($filters);
  }

  function __call ($name, $args)
  {
    if (!$name)
      throw new FilterHandlerNotFoundException("Filter name is missing");
    if (isset($this->filters[$name]))
      return call_user_func_array ($this->filters[$name], $args);

    if (isset($this->fallbackHandler)) {
      if (method_exists ($this->fallbackHandler, $name))
        return call_user_func_array ([$this->fallbackHandler, $name], $args);
    }
    $filters = array_keys ($this->filters);
    sort ($filters);
    throw new FilterHandlerNotFoundException(sprintf ("<p>
<p>Filter: <kbd>%s</kbd>
<p>Arguments: <kbd>%s</kbd>
<p>Registered filters: <pre><code>%s</code></pre>",
      $name,
      print_r (map ($args, function ($e) { return Debug::typeInfoOf ($e); }), true),
      implode ("\n", $filters)
    ));
  }

  /**
   * Chains another filter handler to handle filters that are not handled by the current filter.
   *
   * @param object $handler
   */
  function registerFallbackHandler ($handler)
  {
    $this->fallbackHandler = $handler;
  }

  /**
   * Registers custom filters.
   *
   * @param array|object $filters A map of filter names to filter implementation functions, or an object whose methods
   *                              implement the filters (methods are named filter_xxx).
   */
  function set ($filters)
  {
    // On objects, all methods named `filter_xxx` (where xxx is a filter name) are registered as filters (with the
    // `filter_` prefix stripped).
    if (is_object ($filters)) {
      $keys    = mapAndFilter (array_diff (get_class_methods ($filters), ['__construct']), function ($f) {
        return substr ($f, 0, 7) == 'filter_' ? substr ($f, 7) : null;
      });
      $values  = array_map (function ($v) use ($filters) { return [$filters, "filter_$v"]; }, $keys);
      $filters = array_combine ($keys, $values);
    };
    if (is_array ($filters))
      array_mergeInto ($this->filters, $filters);
    else throw new \InvalidArgumentException("<kbd>filters</kbd> argument is invalid");
  }

}

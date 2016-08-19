<?php
namespace Electro\Plugins\Matisse\Lib;

/**
 * Predefined filters provided by Matisse.
 *
 * ><p>**Note:** the `filter_` prefix allows filter functions to have any name without conflicting with PHP reserved
 * keywords.
 */
class DefaultFilters
{
  /**
   * Alternating values for iterator indexes (0 or 1); allows for specific formatting of odd/even rows.
   *
   * @param int $v
   * @return int
   */
  function filter_alt ($v)
  {
    return $v % 2;
  }

  /**
   * @param string $v
   * @return string
   */
  function filter_currency ($v)
  {
    return formatMoney ($v) . ' €';
  }

  /**
   * @param string $v
   * @return string
   */
  function filter_datePart ($v)
  {
    return explode (' ', $v) [0];
  }

  /**
   * Returns the same value if it's not null, false or an empty string, otherwise returns the specified default value.
   *
   * @param mixed  $v
   * @param string $default
   * @return string
   */
  function filter_else ($v, $default = '')
  {
    return isset ($v) && $v !== '' && $v !== false ? $v : $default;
  }

  /**
   * Returns `true` if a number is even.
   *
   * @param int $v
   * @return boolean
   */
  function filter_even ($v)
  {
    return $v % 2 == 0;
  }

  /**
   * Extracts a field from a list of arrays or objects and returns an array with the same cardinality.
   *
   * ><p>You may use the `join` filter to generate a string from the resulting array.
   *
   * @param array|\Traversable $v
   * @param string             $field The name of the field to be extracted.
   * @return string
   */
  function filter_extract ($v, $field = 'id')
  {
    return map ($v, function ($e) use ($field) { return getField ($e, $field); });
  }

  /**
   * Joins a list of strings into a string.
   *
   * @param array|\Traversable $v    The list of strings to be joined.
   * @param string             $glue The separator between list elements.
   * @return string
   */
  function filter_join ($v, $glue = ', ')
  {
    return implode ($glue . $v);
  }

  /**
   * @param mixed $v
   * @return string
   */
  function filter_json ($v)
  {
    return json_encode ($v, JSON_PRETTY_PRINT);
  }

  /**
   * Sends a value to the debugging console/log, if one exists.
   *
   * @param string $v
   * @return string Empty string.
   */
  function filter_log ($v)
  {
    if (function_exists ('inspect'))
      inspect ($v);
    return '';
  }

  /**
   * Converts line breaks to `<br>` tags.
   *
   * @param $v
   * @return string
   */
  function filter_nl2br ($v)
  {
    return nl2br ($v);
  }

  /**
   * Returns `true` if a number is odd.
   *
   * @param int $v
   * @return boolean
   */
  function filter_odd ($v)
  {
    return $v % 2 == 1;
  }

  /**
   * The ordinal value of an iterator index.
   *
   * @param int $v
   * @return int
   */
  function filter_ord ($v)
  {
    return $v + 1;
  }

  /**
   * @param mixed  $v
   * @param string $true
   * @param string $false
   * @return string
   */
  function filter_then ($v, $true = '', $false = '')
  {
    return $v ? $true : $false;
  }

  /**
   * @param string $v
   * @return string
   */
  function filter_timePart ($v)
  {
    return explode (' ', $v) [1];
  }

  /**
   * @param mixed $v
   * @return string
   */
  function filter_type ($v)
  {
    return typeOf ($v);
  }

}

<?php

namespace Matisse\Lib;

use Auryn\InjectionException;
use Electro\Interfaces\ContentRepositoryInterface;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\Navigation\NavigationLinkInterface;
use Electro\Interfaces\Views\ViewServiceInterface;

/**
 * Predefined filters provided by Matisse.
 *
 * ><p>**Note:** the `filter_` prefix allows filter functions to have any name without conflicting with PHP reserved
 * keywords.
 */
class DefaultFilters
{
  /** @var InjectorInterface */
  private $injector;

  public function __construct (InjectorInterface $injector)
  {
    $this->injector = $injector;
  }

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
   * Converts an asset file path, which is relative to the current module's public directory, to an URL path relative
   * to the application's root.
   *
   * ><p>Note: the current module is the module where the template was loaded from.
   *
   * @param string $v An asset path.
   * @return string
   * @throws InjectionException
   */
  function filter_assetUrl ($v)
  {
    /** @var ViewServiceInterface $viewService */
    $viewService = $this->injector->make (ViewServiceInterface::class);
    $view        = $viewService->currentView ();
    $path        = $viewService->getModuleOfPath ($view->getPath ());
    return "modules/$path/$v";
  }

  /**
   * @param array|\Countable $v
   * @return int
   */
  function filter_count ($v)
  {
    return count ($v);
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
   * @param int    $maxSize
   * @param string $marker
   * @return string
   */
  function filter_cut ($v, $maxSize, $marker = '…')
  {
    return str_cut ($v, $maxSize, $marker);
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
   * Extracts a field from a list of arrays or objects and returns an array, with the same cardinality, containing that
   * field's values.
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
   * Converts a repository asset's file path to a full relative URL.
   *
   * @param string $v An asset path.
   * @return string
   * @throws InjectionException
   */
  function filter_fileUrl ($v)
  {
    /** @var ContentRepositoryInterface $repo */
    $repo = $this->injector->make (ContentRepositoryInterface::class);
    return $repo->getFileUrl ($v);
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
    return implode ($glue, $v ?: []);
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
   * @param string $v
   * @param int    $maxSize
   * @param string $marker
   * @return string
   */
  function filter_limit ($v, $maxSize, $marker = '…')
  {
    return str_truncate ($v, $maxSize, $marker);
  }

  /**
   * @param string $v
   * @param int    $maxSize
   * @param string $marker
   * @return string
   */
  function filter_limitHtml ($v, $maxSize, $marker = '…')
  {
    return trimHTMLText ($v, $maxSize, $marker);
  }

  /**
   * Generates an URL for the current link, replacing all URL parameters by the given argument values at the same
   * ordinal position.
   *
   * ###### Ex:
   * <p><kbd>myLink.url = 'products/&#64;categoryId/&#64;typeId/&#64;prodId'</kbd></p><br>
   * <p>On the template: <kbd>{navigation.myLink|link 32,27,5}</kbd></p>
   * <p>Outputs: <kbd>'products/32/27/5'</kbd>
   * </kbd>
   *
   * @param NavigationLinkInterface $link
   * @param array                   ...$params
   * @return string
   */
  function filter_link (NavigationLinkInterface $link, ...$params)
  {
    return $link->urlOf (...$params);
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
   * Converts a value to lower case.
   *
   * @param string $v
   * @return string
   */
  function filter_lower ($v)
  {
    return mb_strtolower ($v);
  }

  /**
   * Converts line breaks to `<br>` tags.
   *
   * @param $v
   * @return string
   */
  function filter_nl2br ($v)
  {
    return nl2br ($v, false);
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
   * Strips HTML tags and returns plain text. Converts <p> and <br> to line returns.
   *
   * @param string $v
   * @return string
   */
  function filter_plain ($v)
  {
    return strip_tags (preg_replace ('#<br\s*/?>|(?=</(p|h.)>)#i', "\n", $v));
  }

  /**
   * Extracts a field from each record of an array or iterable sequence.
   *
   * @param array|\Iterable $v
   * @param string          $field
   * @return string
   */
  function filter_pluck ($v, $field)
  {
    return map ($v, pluck ($field));
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
   * Returns the type name of the argument. Useful for debugging.
   *
   * @param mixed $v
   * @return string
   */
  function filter_type ($v)
  {
    return typeOf ($v);
  }

  /**
   * Converts a value to upper case.
   *
   * @param string $v
   * @return string
   */
  function filter_upper ($v)
  {
    return mb_strtoupper ($v);
  }

  /**
   * Encodes an URL segment.
   *
   * @param string $v
   * @return string
   */
  function filter_urlencode ($v)
  {
    return urlencode ($v);
  }

}

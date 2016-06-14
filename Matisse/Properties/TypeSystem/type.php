<?php
namespace Selenia\Matisse\Properties\TypeSystem;

use Selenia\Matisse\Components\Internal\Metadata;

/**
 * Represents the available data types of component properties and provides an API for working with both the types
 * themselves and values of those types.
 */
class type
{
  /**
   * Any data type.
   */
  const any = '~any';
  /**
   * Binding expression. This property is a string.
   * Do not define properties of this type. It is used only on macro instances when binding expressions are specified
   * for macro parameters instead of constant values.
   */
  const binding = '~bin';
  /**
   * Boolean (1/0, yes/no, on/off, true/false).
   */
  const bool = '~bol';
  /**
   * A multi-valued metadata property that can be specified as multiple subtags that all share the same name.
   * <p>The type of the values should be specified using the {@see is::of} construct, otherwise a
   * {@see type::metadata} type is assumed.
   * >**Ex:**
   * ```
   *   public $column = [type::collection, is::of, type::content]
   * ```
   */
  const collection = '~col';
  /**
   * A metadata component that holds renderable child components.
   * <p>This is a variant of the metadata property.
   */
  const content = '~con';
  /**
   * Data source. This property type can be an array, an object or an iterable.
   */
  const data = '~dat';
  /**
   * Alphanumeric identifier. Similar to the 'string' type, but with a narrower subset of allowable characters.
   */
  const id = '~id';
  /**
   * A lightweight component that can be used to convey information to the host component; it is not itself renderable.
   * It only contains metadata components. All children will be converted to metadata automatically.
   */
  const metadata = '~mtd';
  /**
   * Int or float.
   */
  const number = '~num';
  /**
   * Plain text. Single-line or multi-line.
   */
  const string = '~str';
  private static $BOOLEAN_VALUES = [
    0       => false,
    1       => true,
    'false' => false,
    'true'  => true,
    'no'    => false,
    'yes'   => true,
    'off'   => false,
    'on'    => true,
  ];
  /**
   * A map of property type identifiers to property type names.
   */
  private static $NAMES = [
    self::any        => 'any',
    self::binding    => 'binding',
    self::bool       => 'bool',
    self::collection => 'collection',
    self::content    => 'content',
    self::data       => 'data',
    self::id         => 'id',
    self::metadata   => 'metadata',
    self::number     => 'number',
    self::string     => 'string',
  ];

  static function getAllNames ()
  {
    return array_values (self::$NAMES);
  }

  /**
   * Converts a type name to a type identifier (one of the `type::XXX` constants).
   *
   * @param string $name
   * @return string|false
   */
  static function getIdOf ($name)
  {
    return array_search ($name, self::$NAMES);
  }

  /**
   * Converts a type identifier (one of the `type::XXX` constants) to a type name.
   *
   * @param string $id
   * @return string|false
   */
  static function getNameOf ($id)
  {
    return get (self::$NAMES, $id, false);
  }

  /**
   * Converts a boolean textual representation into a true boolean value.
   *
   * @param string $v
   * @return bool
   */
  static function toBoolean ($v)
  {
    return is_string ($v) ? get (self::$BOOLEAN_VALUES, $v, false) : boolval ($v);
  }

  /**
   * Converts a value that will be assigned to a property into a type compatible with that
   * property.
   * >**Note:** you should call {@see validate()} before calling this method, as the later does not
   * validate its input.
   *
   * @param string $type The property type.
   * @param mixed  $v    The value to be converted.
   * @return bool|float|int|null|string|\Traversable
   */
  public static function typecast ($type, $v)
  {
    if (isset ($v) && $v !== '')

      switch ($type) {

        case type::bool:
          return type::toBoolean ($v);

        case type::id:
          return $v;

        case type::number:
          return is_float ($v) ? floatval ($v) : intval ($v);

        case type::string:
          return strval ($v);

        case type::data:
          if ($v instanceof \Iterator)
            return $v;
          if ($v instanceof \IteratorAggregate)
            return $v->getIterator ();
          return $v;
      }

    return $v;
  }

  /**
   * Validates a value against a specific type.
   *
   * @param string $type
   * @param mixed  $v
   * @return bool
   */
  static function validate ($type, $v)
  {
    if (is_null ($v) || $v === '')
      return true;

    switch ($type) {

      case type::binding:
        return is_string ($v);

      case type::any:
      case type::bool: // Any value can be typecast to boolean.
        return true;

      case type::data:
        return is_array ($v) || is_object ($v)
               || $v instanceof \Traversable
               || (is_string ($v) && strpos ($v, '{') !== false);

      case type::id:
        return !!preg_match ('#^[\w\-]+$#', $v);

      case type::content:
      case type::metadata:
        return $v instanceof Metadata;

      case type::collection:
        return is_array ($v);

      case type::number:
        return is_numeric ($v);

      case type::string:
        return is_scalar ($v) || (is_object ($v) && method_exists ($v, '__toString'));
    }
    return false;
  }

}

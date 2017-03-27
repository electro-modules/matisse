<?php
use Electro\Interfaces\RenderableInterface;
use Matisse\Exceptions\MatisseException;
use Matisse\Lib\DataBinder;

const MPARENT   = '@parent';
const MPROPS    = '@props';
const MCHILDREN = '@nodes';
const MBINDINGS = '@bind';
const MTAG      = '@tag';

/**
 * Extracts and escapes text from the given value, for outputting to the HTTP client.
 *
 * <p>Note: this returns escaped text, except if the given argument is a {@see RawText} instance, in which case it
 * returns raw text.
 *
 * @param string|RawText $s
 * @return string
 * @throws MatisseException
 */
function _e ($s)
{
  if (!is_scalar ($s)) {
    if (is_null ($s)) return '';
    if (is_object ($s) &&
        ($s instanceof RawText || $s instanceof RenderableInterface || method_exists ($s, '__toString'))
    )
      return (string)$s;
    else {
      if (is_iterable ($s))
        return iteratorOf ($s)->current ();
      return sprintf ('[%s]', typeOf ($s));
    }
  }
  return htmlentities ($s, ENT_QUOTES, 'UTF-8', false);
}

/**
 * Converts a dash-separated tag name into a camel case tag name.
 * > Ex: `<my-tag>` -> `<myTag>`
 *
 * @param string $name
 * @return string
 */
function normalizeTagName ($name)
{
  return str_replace (' ', '', ucwords (str_replace ('-', ' ', $name)));
}

function normalizeAttributeName ($name)
{
  return lcfirst (str_replace (' ', '', ucwords (str_replace ('-', ' ', $name))));
}

/**
 * Unified interface for retrieving a value by property/method name from an object or by key from an array.
 *
 * ### On an object
 * - If a property XXX is inaccessible, it calls `getXXX()` if it exists.
 * - If a property XXX does not exist, it tries to call `XXX()` if it exists.
 * - If the object is indexable, it returns `$obj['XXX']` if it exists.
 * - Otherwise it returns the default value.
 *
 * ### On an array
 * - Returns the item at the specified key, or the default value if the key doesn't exist.
 *
 * @param array|object $data
 * @param string       $key
 * @param mixed        $default Value to return if the key doesn't exist or it's not accessible trough know methods.
 * @return mixed
 */
function _g ($data, $key, $default = null)
{
  if (is_object ($data)) {
    if ($data instanceof DataBinder || ($data instanceof \ArrayAccess && $data->offsetExists ($key)))
      return $data[$key];
    if (isset($data->$key))
      return $data->$key;
    // Property may be private/protected or virtual, try to call a getter method with the same name
    if (method_exists ($data, $key) || method_exists ($data, '__call'))
      try {
        return $data->$key ();
      }
      catch (BadMethodCallException $e) {
      }
    // No getter was found, so if the property exists, it is either inaccessible or it is null, either way return the default
    if (property_exists ($data, $key))
      return $default;
  }
  elseif (is_array ($data))
    return array_key_exists ($key, $data) ? $data[$key] : $default;
  return $default;
}

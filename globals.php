<?php
//------------------------------
//  Matisse-specific functions
//------------------------------
use Selenia\Interfaces\RenderableInterface;
use Selenia\Matisse\Exceptions\MatisseException;

/**
 * Represents text that should not be HTML-escaped when output.
 */
class RawText
{
  private $s;

  function __construct ($s)
  {
    if (is_null ($s))
      $this->s = '';
    elseif ($s instanceof RenderableInterface) {
      $this->s = $s->getRendering ();
      return;
    }
    elseif (!is_string ($s))
      throw new MatisseException ("A <kbd>RawText</kbd> instance must hold a string value, not a " . typeInfoOf ($s));
    $this->s = $s;
  }

  /**
   * Note: this is not `__toString` on purpose.
   *
   * @return string
   * @throws MatisseException
   */
  function toString ()
  {
    return $this->s;
  }
}

/**
 * Returns escaped text, except if the given argument is a {@see RawText} instance.
 *
 * @param string|RawText $s
 * @return string
 */
function _e ($s)
{
  if (!is_scalar ($s)) {
    if (is_null ($s)) return '';
    if ($s instanceof RawText) return $s->toString ();
    if ($s instanceof RenderableInterface)
      $s = $s->getRendering ();
    elseif (is_object ($s) && method_exists ($s, '__toString'))
      $s = (string)$s;
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
 * - If a property is inaccessible, it calls `getProperty()` if it exists, otherwise it returns the default value.
 * - If a property does not exist, it tries to call `property()` if it exists, otherwise, it returns the default value.
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
    // There's no property, but the object may be indexable
    if ($data instanceof \ArrayAccess && $data->offsetExists ($key))
      return $data[$key];
  }
  elseif (is_array ($data))
    return array_key_exists ($key, $data) ? $data[$key] : $default;
  return $default;
}

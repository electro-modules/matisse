<?php
namespace Selenia\Matisse\Properties\Base;

use JsonSerializable;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Properties\TypeSystem\type;

/**
 * Properties of a Metadata component.
 */
class MetadataProperties extends AbstractProperties implements JsonSerializable
{
  /**
   * Dynamic set of attributes, as specified on the source markup.
   *
   * @var array
   */
  protected $props = [];

  function __get ($name)
  {
    return $this->get ($name);
  }

  function __set ($name, $value)
  {
    $this->set ($name, $value);
  }

  function __isset ($name)
  {
    return $this->defines ($name) && !is_null ($this->get ($name));
  }

  function __unset ($name)
  {
    unset ($this->props[$name]);
  }

  function defines ($name, $asSubtag = false)
  {
    return true;
  }

  function get ($name, $default = null)
  {
    if (property_exists ($this, $name))
      return $this->$name;
    if (array_key_exists ($name, $this->props))
      return $this->props [$name];
    return $default;
  }

  function getAll ()
  {
    return array_merge (object_publicProps ($this), $this->props);
  }

  function getDefaultValue ($name)
  {
    if (property_exists ($this, $name)) {
      $c     = new \ReflectionClass($this);
      $props = $c->getDefaultProperties ();
      return isset($props[$name]) ? $props[$name] : null;
    }
    return null;
  }

  /**
   * Gets a map of the dynamic (non-predefined) properties of the component.
   * <p>Properties declared on the class are excluded.
   *
   * @return array A map of property names to property values.
   */
  function getDynamic ()
  {
    return $this->props;
  }

  function getEnumOf ($name)
  {
    return [];
  }

  function getPropertyNames ()
  {
    return array_merge (object_propNames ($this), array_keys ($this->props));
  }

  function getRelatedTypeOf ($name)
  {
    return type::content;
  }

  function getTypeOf ($name)
  {
    return null;
  }

  function isEnum ($name)
  {
    return false;
  }

  function isScalar ($name)
  {
    return isset($this->name) ? is_scalar ($this->name) : true;
  }

  /**
   * **Note:** this is useful for the `json` filter, for instance.
   *
   * @return array
   */
  function jsonSerialize ()
  {
    return $this->getAll ();
  }

  function set ($name, $value)
  {
    // This is relevant only to subclasses.
    if (!$this->defines ($name))
      throw new ComponentException(null, "Undefined parameter <kbd>$name</kbd>.");

    $value = $this->typecastPropertyValue ($name, $value);

    if (property_exists ($this, $name))
      $this->$name = $value;
    else $this->props[$name] = $value;

    if ($this->isModified ($name))
      $this->onPropertyChange ($name);
  }

}

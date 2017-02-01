<?php
namespace Matisse\Properties\Base;

use Matisse\Components\Base\Component;
use Matisse\Components\Metadata;
use Matisse\Components\Text;
use Matisse\Exceptions\ComponentException;
use Matisse\Exceptions\DataBindingException;
use Matisse\Exceptions\ReflectionPropertyException;
use Matisse\Interfaces\ComponentPropertiesInterface;
use Matisse\Properties\TypeSystem\type;
use PhpKit\WebConsole\Lib\Debug;

abstract class AbstractProperties implements ComponentPropertiesInterface, \JsonSerializable
{
  /**
   * The component who owns these properties.
   *
   * @var Component
   */
  protected $component;
  /**
   * @var array
   */
  private $beingAssigned;

  function __construct (Component $ownerComponent)
  {
    $this->component = $ownerComponent;
  }

  /**
   * Checks if the component supports setting/getting a specific attribute.
   *
   * @param string $propName
   * @param bool   $asSubtag When true, the attribute MUST be able to be specified in subtag form.
   *                         When false, the attribute can be either a tag attribute or a subtag.
   * @return bool
   */
  abstract function defines ($propName, $asSubtag = false);

  /**
   * Returns all property values, indexed by property name.
   *
   * @return array A map of property name => property value.
   */
  abstract function getAll ();

  /**
   * @param string $propName Property name.
   * @return mixed
   * @throws ReflectionPropertyException
   */
  abstract function getDefaultValue ($propName);

  /**
   * @param string $propName Property name.
   * @return array Always returns an array, even if no enumeration is defined for the target property.
   * @throws ReflectionPropertyException
   */
  abstract function getEnumOf ($propName);

  /**
   * Returns all declared property names.
   *
   * @return string[]
   */
  abstract function getPropertyNames ();

  /**
   * Returns the type ID of a property's secondary type (usually, a collection item type).
   *
   * @param string $propName
   * @return string
   */
  abstract function getRelatedTypeOf ($propName);

  /**
   * Returns the type ID of a property.
   *
   * @param string $propName
   * @return string
   */
  abstract function getTypeOf ($propName);

  /**
   * Checks if a property type is restricted to a set of allowed values.
   *
   * @param string $propName
   * @return bool
   */
  abstract function isEnum ($propName);

  function __debugInfo ()
  {
    return $this->getAll ();
  }

  /**
   * Mass-assigns a set of properties.
   *
   * @param array $props
   * @throws ComponentException
   * @throws ReflectionPropertyException
   */
  function apply (array $props)
  {
    $this->beingAssigned = $props;
    foreach ($props as $k => $v)
      $this->set ($k, $v);
  }

  /**
   * Mass-assigns a set of properties, but only to those properties that are not yet modified.
   *
   * @param array $props
   * @throws ComponentException
   * @throws ReflectionPropertyException
   */
  function applyDefaults (array $props)
  {
    $this->beingAssigned = $props;
    foreach ($props as $k => $v)
      if (!$this->isModified ($k))
        $this->set ($k, $v);
  }

  /**
   * Checks if a property can be specified on markup as a subtag.
   *
   * @param string $propName
   * @return bool
   */
  function canBeSubtag ($propName)
  {
    if ($this->defines ($propName)) {
      $type = $this->getTypeOf ($propName);
      switch ($type) {
        case type::content:
        case type::collection:
        case type::metadata:
        case type::string:
        case type::number:
          return true;
      }
    }
    return false;
  }

  /**
   * Throws an exception if the specified property is not available.
   *
   * @param $propName
   * @throws ComponentException
   */
  function ensurePropertyExists ($propName)
  {
    if (!$this->defines ($propName)) {
      throw new ComponentException(
        $this->component,
        sprintf ("Invalid property <kbd>%s</kbd> specified for a %s instance.", $propName, Debug::typeInfoOf ($this))
      );
    }
  }

  /**
   * Gets the raw value of the specified property, not performing data binding.
   *
   * @param string $propName
   * @param mixed  $default [optional]
   * @return mixed
   */
  function get ($propName, $default = null)
  {
    return property ($this, $propName, $default);
  }

  /**
   * Returns the values being assigned to this instance. It is used for debugging; it's displayed when a validation
   * error occurs while assigning the values.
   *
   * @return array
   */
  function getBeingAssigned ()
  {
    return $this->beingAssigned;
  }

  /**
   * Gets the value of the specified property, performing data binding if the property is bound.
   *
   * @param string $propName
   * @param mixed  $default [optional]
   * @return mixed
   * @throws ComponentException
   * @throws DataBindingException
   */
  function getComputed ($propName, $default = null)
  {
    $v = $this->component->getComputedPropValue ($propName);
    return exists ($v) ? $v : $default;
  }

  /**
   * Returns a subset of the available properties, filtered by the a specific type IDs.
   *
   * @param string[] $types One or moe of the {@see type}::XXX constants.
   * @return array A map of property name => property value.
   */
  function getPropertiesOf (...$types)
  {
    $result = [];
    $names  = $this->getPropertyNames ();
    if (isset($names))
      foreach ($names as $name)
        if (in_array ($this->getTypeOf ($name), $types))
          $result[$name] = $this->get ($name);
    return $result;
  }

  /**
   * Returns the type name of a property's secondary type (usually, a collection item type).
   *
   * @param string $propName
   * @return false|string
   */
  function getRelatedTypeNameOf ($propName)
  {
    $id = $this->getRelatedTypeOf ($propName);
    return type::getNameOf ($id);
  }

  /**
   * Returns the type name of a property.
   *
   * @param string $propName
   * @return false|string
   */
  function getTypeNameOf ($propName)
  {
    $id = $this->getTypeOf ($propName);
    return type::getNameOf ($id);
  }

  /**
   * Checks if a property exists and its value is not empty (null or '').
   *
   * @param string $propName
   * @return bool
   */
  function has ($propName)
  {
    return $this->defines ($propName) ? exists ($this->get ($propName)) : false;
  }

  /**
   * Checks if a property's value is different from the default one, or if it has been explicitly set.
   *
   * @param string $propName Property name.
   * @return bool
   * @throws ReflectionPropertyException
   */
  function isModified ($propName)
  {
    return $this->get ($propName) !== $this->getDefaultValue ($propName);
  }

  /**
   * Checks if a property is of a scalar type.
   *
   * @param string $propName
   * @return bool
   */
  function isScalar ($propName)
  {
    $type = $this->getTypeOf ($propName);
    return $type == type::bool || $type == type::id || $type == type::number ||
           $type == type::string;
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

  /**
   * Validates, typecasts and assigns a value to a property.
   *
   * @param string $propName
   * @param mixed  $value
   * @throws ComponentException
   * @throws ReflectionPropertyException
   */
  function set ($propName, $value)
  {
    $this->ensurePropertyExists ($propName);
    $this->$propName = $this->typecastPropertyValue ($propName, $value);

    if ($this->isModified ($propName))
      $this->onPropertyChange ($propName);
  }

  /**
   * Assign a new owner to the properties object. This will also do a deep clone of the component's properties.
   *
   * @param Component $owner
   */
  function setComponent (Component $owner)
  {
    $this->component = $owner;
    $props           = $this->getPropertiesOf (type::content);
    foreach ($props as $name => $value)
      if (!is_null ($value)) {
        /** @var Component $c */
        $c = clone $value;
        $c->attachTo ($owner);
        $this->$name = $c;
      }
    $props = $this->getPropertiesOf (type::collection);
    foreach ($props as $name => $values)
      if (!empty($values))
        $this->$name = Component::cloneComponents ($values, $owner);
  }

  /**
   * Returns the value converted to a the data type required by the specified property.
   *
   * @param string $name
   * @param mixed  $v
   * @return bool|float|int|null|string|\Traversable
   * @throws ComponentException
   * @throws ReflectionPropertyException
   */
  function typecastPropertyValue ($name, $v)
  {
    if ($this->isScalar ($name) && $this->isEnum ($name))
      $this->validateEnum ($name, $v);

    $type = $this->getTypeOf ($name);
    if ($type && !type::validate ($type, $v))
      throw new ComponentException ($this->component,
        sprintf (
          "%s is not a valid value for the <kbd>$name</kbd> property, which is of type <kbd>%s</kbd>",
          is_scalar ($v)
            ? sprintf ("The %s<kbd>%s</kbd>", typeOf ($v), var_export ($v, true))
            : sprintf ("A value of PHP type <kbd>%s</kbd>", typeOf ($v)),
          type::getNameOf ($type)
        ));

    // A special case for content type properties:
    // Convert a content property specified as attribute=string to a format equivalent to the one generated by
    // <subtag>string</subtag>
    if ($type == type::content && is_string ($v)) {
      $content = new Metadata($this->component->context, $name, type::metadata);
      $content->setChildren ([Text::from ($this->component->context, $v)]);
      return $content;
    }

    return type::typecast ($type, $v);
  }

  /**
   * Called whenever a property's value changes.
   *
   * @param string $propName
   */
  protected function onPropertyChange ($propName)
  {
    // noop
  }

  /**
   * Throws an exception if the the specified value is not valid for the given enumerated property.
   *
   * @param string $name
   * @param mixed  $v
   * @throws ComponentException
   * @throws ReflectionPropertyException
   */
  protected function validateEnum ($name, $v)
  {
    $enum = $this->getEnumOf ($name);
    if (array_search ($v, $enum) === false) {
      $list = implode ('</b>, <b>', $enum);
      throw new ComponentException ($this->component,
        "Invalid value for attribute/parameter <b>$name</b>.\nExpected: <b>$list</b>.");
    }
  }

}

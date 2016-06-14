<?php
namespace Selenia\Matisse\Properties\TypeSystem;

use Iterator;
use Selenia\Matisse\Exceptions\ReflectionException;
use Selenia\Matisse\Exceptions\ReflectionPropertyException;
use Selenia\Matisse\Interfaces\ComponentPropertiesInterface;

class ReflectionClass
{
  /**
   * @var string The full class name.
   */
  private $name;
  /**
   * @var ReflectionProperty[] A map of property names => reflection info.
   */
  private $props = [];

  /**
   * @param string $className The name of a class that implements {@see ComponentPropertiesInterface}
   * @throws ReflectionException
   */
  function __construct ($className)
  {
    if (!implementsInterface ($className, ComponentPropertiesInterface::class))
      throw new ReflectionException ("Class %s does not implement %s", formatClassName ($className),
        formatClassName (ComponentPropertiesInterface::class));
    $this->name = $className;
  }

  static private function isMetadataKeyword ($s)
  {
    return is_string ($s) && $s != '' && $s[0] == '~';
  }

  function getName ()
  {
    return $this->name;
  }

  /**
   * Returns all property names and corresponding reflection information.
   *
   * @return ReflectionProperty[] A map of property names => reflection info.
   */
  function getProperties ()
  {
    return $this->props;
  }

  /**
   * @param string $name Property name.
   * @return ReflectionProperty
   * @throws ReflectionPropertyException
   */
  function getProperty ($name)
  {
    if (isset($this->props[$name]))
      return $this->props[$name];
    throw new ReflectionPropertyException ($this->name, $name, "Unknown property");
  }

  function hasProperty ($name)
  {
    return isset($this->props[$name]);
  }

  /**
   * Initializes the given instance of the target class to the default values set by metadata.
   *
   * @param ComponentPropertiesInterface $o An instance of the target class.
   * @return mixed The new instance.
   */
  function init (ComponentPropertiesInterface $o)
  {
    foreach ($this->props as $p => $i)
      $o->$p = $i->default;
    return $o;
  }

  /**
   * Creates a new instance of the target class, initialized to the default values set by metadata.
   *
   * @param array $args Optional constructor arguments for the new instance.
   * @return ComponentPropertiesInterface The new instance.
   */
  function newInstance (...$args)
  {
    return $this->init (new $this->name (...$args));
  }

  function parseMetadataFromPropertyDefaults ()
  {
    $refClass = new \ReflectionClass($this->name);
    $defaults = $refClass->getDefaultProperties ();
    foreach ($refClass->getProperties (\ReflectionProperty::IS_PUBLIC) as $property)
      $this->setupProp ($name = $property->name, get ($defaults, $name));
  }

  private function getNextKeyword ($it, $propName)
  {
    $arg = $this->getNextMetaArg ($it, 'string', $propName);
    if (!self::isMetadataKeyword ($arg))
      throw new ReflectionPropertyException($this->name, $propName,
        "Invalid property type declaration.<p>Expected metadata keyword, <kbd>%s</kbd> given", var_export ($arg, true));
    return $arg;
  }

  private function getNextMetaArg (Iterator $it, $expected, $propName)
  {
    $it->next ();
    if ($it->valid ()) {
      $e = $it->current ();
      if (typeOf ($e) == $expected)
        return $e;
      else throw new ReflectionPropertyException($this->name, $propName,
        "Invalid metadata argument; <kbd>%s</kbd> expected, <kbd>%s</kbd> given", $expected, typeOf ($e));
    }
    else throw new ReflectionPropertyException($this->name, $propName, "Missing metadata argument");
  }

  private function parseMetadata (ReflectionProperty $prop, $value, Iterator $it)
  {
    switch ($value) {

      case type::string:
      case type::id:
        $prop->type = $value;
        break;

      case type::number:
        $prop->type = $value;
        break;

      case type::bool:
        $prop->type = $value;
        if (!isset($prop->default))
          $prop->default = false;
        break;

      case type::content:
        $prop->type    = $value;
        $prop->default = null;
        break;

      case type::data:
        $prop->type    = $value;
        $prop->default = null;
        break;

      case type::metadata:
        $prop->type    = $value;
        $prop->default = null;
        break;

      case type::collection:
        $prop->type = $value;
        if (!isset ($prop->default))
          $prop->default = [];
        if (!isset ($prop->relatedType))
          $prop->relatedType = type::content;
        break;

      case type::any:
        $prop->type = $value;
        $prop->default = null;
        break;

      case type::binding:
        $prop->type = $value;
        break;

      case is::required:
        $prop->required = true;
        break;

      case is::enum:
        $prop->enum = $this->getNextMetaArg ($it, 'array', $prop->name);
        break;

      case is::of:
        $prop->relatedType = $this->getNextKeyword ($it, $prop->name);
        break;

      default:
        throw new ReflectionPropertyException($this->name, $prop->name,
          "Invalid property type declaration.<p>Given value: <kbd>%s</kbd>", var_export ($value, true));
    }
  }

  /**
   * Set the default value explicitly.
   *
   * @param ReflectionProperty $prop
   * @param mixed              $v
   */
  private function setMetadataFromDefaultValue (ReflectionProperty $prop, $v)
  {
    $prop->default = $v;

    // If the type has not yet been defined, it will be defined implicitly via the default value.
    if (!isset($prop->type))
      switch (gettype ($v)) {
        case 'string':
          $prop->type = type::string;
          break;
        case 'boolean':
          $prop->type = type::bool;
          break;
        case 'integer':
        case 'double':
          $prop->type = type::number;
          break;
        case 'NULL':
          // Don't bother setting a NULL default.
          break;
      }
  }

  private function setupProp ($name, $declaration)
  {
    $prop = $this->props[$name] = new ReflectionProperty($name);
    if (!is_array ($declaration))
      $declaration = [$declaration];
    $it = new \ArrayIterator($declaration);
    while ($it->valid ()) {
      $v = $it->current ();
      if (self::isMetadataKeyword ($v))
        $this->parseMetadata ($prop, $v, $it);
      else $this->setMetadataFromDefaultValue ($prop, $v);
      $it->next ();
    }
    if (!isset($prop->type))
      throw new ReflectionPropertyException($this->name, $name, "Missing type declaration");
  }

}

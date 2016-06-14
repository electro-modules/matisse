<?php
namespace Selenia\Matisse\Properties\Base;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Properties\TypeSystem\Reflection;
use Selenia\Matisse\Properties\TypeSystem\ReflectionClass;

class ComponentProperties extends AbstractProperties
{
  /**
   * @var ReflectionClass
   */
  protected $metadata;

  function __construct (Component $ownerComponent)
  {
    parent::__construct ($ownerComponent);
    $this->metadata = Reflection::instance ()->of (get_class ($this));
    $this->metadata->init ($this);
  }

  function __get ($name)
  {
    throw new ComponentException ($this->component, "Can't read non existing property <b>$name</b>.");
  }

  function __set ($name, $value)
  {
    throw new ComponentException ($this->component, "Can't set non existing property <b>$name</b>.");
  }

  function defines ($name, $asSubtag = false)
  {
    if ($asSubtag) return $this->canBeSubtag ($name);
    return $this->metadata->hasProperty ($name);
  }

  function getAll ()
  {
    $p = $this->getPropertyNames ();
    $r = [];
    foreach ($p as $prop)
      $r[$prop] = $this->$prop;
    return $r;
  }

  function getDefaultValue ($propName)
  {
    return $this->metadata->getProperty ($propName)->default;
  }

  function getEnumOf ($propName)
  {
    return $this->metadata->getProperty ($propName)->enum ?: [];
  }

  function getPropertyNames ()
  {
    return array_keys ($this->metadata->getProperties ());
  }

  function getRelatedTypeOf ($propName)
  {
    return $this->metadata->getProperty ($propName)->relatedType;
  }

  function getTypeOf ($propName)
  {
    return $this->metadata->getProperty ($propName)->type;
  }

  function isEnum ($propName)
  {
    return isset($this->metadata->getProperty ($propName)->enum);
  }

}

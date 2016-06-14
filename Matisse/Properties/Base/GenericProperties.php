<?php
namespace Selenia\Matisse\Properties\Base;

/**
 * Generic Attributes allow a component instance to have any tag attributes, without them having to be specifically
 * declared for the component class.
 *
 * ><p>**Note:** attributes specified as subtags cannot be generic.
 */
class GenericProperties extends HtmlComponentProperties
{
  public function __get ($name)
  {
    if (property_exists ($this, $name)) return $this->$name;
    return null;
  }

  public function __set ($name, $value)
  {
    $this->$name = $value;
  }

  public function defines ($name, $asSubtag = false)
  {
    return !$asSubtag;
  }

  public function __isset ($name)
  {
    return property_exists ($this, $name);
  }

}

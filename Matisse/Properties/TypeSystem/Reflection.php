<?php
namespace Selenia\Matisse\Properties\TypeSystem;

use Selenia\Traits\SingletonTrait;

class Reflection
{
  use SingletonTrait;

  /**
   * @var ReflectionClass[] A map of class name => reflection info.
   */
  private $classes = [];

  private function __construct () { }

  function of ($className, $autoInit = true)
  {
    $v = get ($this->classes, $className);
    if ($v) return $v;
    $v = $this->classes[$className] = new ReflectionClass ($className);
    if ($autoInit) $v->parseMetadataFromPropertyDefaults ();
    return $v;
  }
}

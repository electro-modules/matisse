<?php
namespace Selenia\Matisse\Properties\TypeSystem;

class ReflectionProperty
{
  /**
   * The property's default value.
   * @var mixed
   */
  public $default;
  /**
   * The property's valid values (optional). `null` if not applicable.
   * @var array|null
   */
  public $enum;
  /**
   * The property's name.
   * @var string
   */
  public $name;
  /**
   * An additional data type related to the property. One of the {@see type}::XXX constants.
   *
   * <p>This data type can be used for:
   * - declaring the type of a collection property's elements.
   * @var string
   */
  public $relatedType;
  /**
   * Must it be set explicitly to a non-null value?
   * @var bool
   */
  public $required = false;
  /**
   * The property's data type. One of the {@see type}::XXX constants.
   * @var string
   */
  public $type;

  function __construct ($name)
  {
    $this->name = $name;
  }

}

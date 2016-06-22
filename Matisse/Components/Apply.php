<?php
namespace Electro\Plugins\Matisse\Components;

use Electro\Plugins\Matisse\Components\Base\Component;
use Electro\Plugins\Matisse\Components\Internal\Metadata;
use Electro\Plugins\Matisse\Interfaces\PresetsInterface;
use Electro\Plugins\Matisse\Properties\Base\ComponentProperties;
use Electro\Plugins\Matisse\Properties\TypeSystem\type;

class ApplyProperties extends ComponentProperties
{
  /**
   * @var bool
   */
  public $recursive = false;
  /**
   * @var Metadata|null
   */
  public $set = type::metadata;
  /**
   * @var string
   */
  public $where = '';
}

/**
 * A component that applies a set of property values to its children, optionally filtered by tag name.
 *
 * This is useful when the properties have dynamic values, otherwise use 'presets'.
 *
 * ##### Syntax:
 * ```
 * <Apply [where="tag-name"]>
 *   <Set prop1="value1" ... propN="valueN"/>
 *   content
 * </Apply>
 *  ```
 * <p>If no filter is provided, nothing will happen.
 * > **Note:** you can use data bindings on the property values of `<Set>`
 */
class Apply extends Component implements PresetsInterface
{
  const allowsChildren = true;

  const propertiesClass = ApplyProperties::class;

  /** @var ApplyProperties */
  public $props;

  private $applyProps;

  function applyPresets (Component $component)
  {
    if ($component->getTagName () == $this->props->where)
      $component->props->applyDefaults ($this->applyProps);
  }

  protected function render ()
  {
    $setterProp = $this->props->set;
    $setterProp->databind ();
    $this->applyProps         = $setterProp->props->getAll ();
    $this->context->presets[] = $this;
    $this->runChildren ();
    array_pop ($this->context->presets);
  }

}

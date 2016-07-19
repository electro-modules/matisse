<?php
namespace Electro\Plugins\Matisse\Components;

use Electro\Plugins\Matisse\Components\Base\Component;
use Electro\Plugins\Matisse\Components\Base\HtmlComponent;
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
 * <Apply [where="selector"]>
 *   <Set prop1="value1" ... propN="valueN"/>
 *   content
 * </Apply>
 *  ```
 * <p>If no filter is provided, nothing will happen.
 * > **Note:** you can use data bindings on the property values of `<Set>`
 *
 * <p>The selector is partially CSS-compatible.
 * <p>Valid values have the following syntax:
 * >`tagName` | `.className` | `tagName.className`
 */
class Apply extends Component implements PresetsInterface
{
  const allowsChildren = true;

  const propertiesClass = ApplyProperties::class;

  /** @var ApplyProperties */
  public $props;

  /** @var array */
  private $applyProps;
  /** @var string */
  private $matchClass;
  /** @var string */
  private $matchTag;

  function applyPresets (Component $component)
  {
    if ($this->matchTag && $this->matchTag !== $component->getTagName ())
      return;
    inspect($this->matchClass,$component->props && $component->props->defines('class')? $component->props->class : null);
    if ($this->matchClass &&
        (!$component instanceof HtmlComponent || !preg_match ($this->matchClass, $component->props->class))
    )
      return;
    $component->props->applyDefaults ($this->applyProps);
  }

  protected function render ()
  {
    $setterProp = $this->props->set;
    $setterProp->databind ();
    $this->applyProps         = $setterProp->props->getAll ();
    $this->context->presets[] = $this;
    $rule                     = $this->props->where;
    $this->matchTag           = str_extract ($rule, '/^[\w\-]+/');
    $class                    = str_extract ($rule, '/^\.([\w\-]+)/');
    if ($class)
      $this->matchClass = sprintf ('/(?:^| )%s(?:$| )/', preg_quote ($class));
    $this->runChildren ();
    array_pop ($this->context->presets);
  }

}

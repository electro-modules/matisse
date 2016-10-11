<?php
namespace Electro\Plugins\Matisse\Components;

use Electro\Plugins\Matisse\Components\Base\Component;
use Electro\Plugins\Matisse\Components\Internal\Metadata;
use Electro\Plugins\Matisse\Interfaces\PresetsInterface;
use Electro\Plugins\Matisse\Lib\Preset;
use Electro\Plugins\Matisse\Properties\Base\ComponentProperties;
use Electro\Plugins\Matisse\Properties\TypeSystem\is;
use Electro\Plugins\Matisse\Properties\TypeSystem\type;

class PresetsProperties extends ComponentProperties
{
  /**
   * @var Metadata|null
   */
  public $where = [type::collection, is::of, type::metadata];
}

/**
 * A component that defines preset property values for some of its children, which are targeted by tag name and/or
 * CSS class name.
 *
 * ##### Syntax:
 * ```
 * <Preset>
 *   <Where match="selector">
 *     <Set prop1="value1" ... propN="valueN"/>
 *     <Unset prop1 ... propN/>
 *     <Prepend>
 *       optional content to be prepended to the target
 *     </Prepend>
 *     <Append>
 *       optional content to be appended to the target
 *     </Append>
 *     optional content to be replaced on the target
 *   </Where>
 *   <Where...>...</Where>
 *   content to be precessed
 * </Apply>
 *  ```
 * <p>If no filter is provided, no children will be affected.
 * <p>You can use data bindings on the property values of `<Set>`
 *
 * <p>The selector is partially CSS-compatible.
 * <p>Valid values have the following syntax:
 * >`tagName` | `.className` | `[prop]` | `[prop=value]` or a combination of these, on this order.
 * ><p>Ex: `tagName[prop=value]`
 *
 * ><p>Note: `tagName[prop]` matches any `tagName` that has a non-empty `prop` property.
 * ><p>Note that `<tag prop>` is equivalent to `<tag prop=true>`, not to `<tag prop="">`.
 */
class Presets extends Component implements PresetsInterface
{
  const allowsChildren = true;

  const propertiesClass = PresetsProperties::class;

  /** @var PresetsProperties */
  public $props;

  /**
   * @var \Electro\Plugins\Matisse\Lib\Preset[]
   */
  private $presets;

  function applyPresets (Component $component)
  {
    foreach ($this->presets as $preset)
      $preset->ifMatchesApply ($component);
  }

  protected function render ()
  {
    $this->presets = [];
    /** @var Metadata $where */
    foreach ($this->props->where as $where) {
      $whereSubtags = $where->getChildren ();
      if (!$whereSubtags)
        continue;
      $where->databind ();
      $rule = $where->props->match;
      if (!$rule)
        continue;
      $preset  = new Preset ($rule);
      $append  = [];
      $prepend = [];
      $replace = [];
      $unset   = [];
      foreach ($whereSubtags as $subTag) {
        switch ($subTag->getTagName ()) {
          case 'Set':
            $subTag->databind ();
            $newProps      = $subTag->props->getAll ();
            $preset->props = isset($preset->props) ? array_merge ($preset->props, $newProps) : $newProps;
            break;
          case 'Unset':
            if (isset($preset->props)) {
              $subTag->databind ();
              $newProps = array_keys ($subTag->props->getAll ());
              array_mergeInto ($unset, $newProps);
            }
            break;
          case 'Prepend':
            array_mergeInto ($prepend, $subTag->getChildren ());
            break;
          case 'Append':
            array_mergeInto ($append, $subTag->getChildren ());
            break;
          default: //overwrite
            $replace[] = $subTag;
        }
      }
      $preset->prepend = $prepend;
      $preset->append  = $append;
      $preset->content = $replace;
      $preset->unset   = $unset;
      $this->presets[] = $preset;
    }
    $stack                    = $this->context->presets; // Save a copy of the current presets stack.
    $this->context->presets[] = $this;
    $this->runChildren ();
    $this->context->presets = $stack; // Note: it is not safe to just do an array_pop() here.
  }

}

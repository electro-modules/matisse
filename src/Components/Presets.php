<?php
namespace Matisse\Components;

use Matisse\Components\Base\Component;
use Matisse\Exceptions\ComponentException;
use Matisse\Interfaces\PresetsInterface;
use Matisse\Lib\Preset;
use Matisse\Properties\Base\ComponentProperties;
use Matisse\Properties\TypeSystem\is;
use Matisse\Properties\TypeSystem\type;

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
 *     <Set name="prop1" type="content">
 *        <div>some content</div>
 *     </Set>
 *     <Set name="prop1" type="metadata">
 *        <Column title="Test" width=100/>
 *     </Set>
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
 *   target content to be processed
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
   * @var \Matisse\Lib\Preset[]
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
            if ($subTag->hasChildren ()) {
              $name = $subTag->props->name;
              if (!$name)
                throw new ComponentException($this,
                  "A <kbd>Set</kbd> subtag with content must define a <kbd>name</kbd> attribute");
              $type = $subTag->props->type;
              if ($type != 'content' && $type != 'metadata')
                throw new ComponentException($this,
                  "A <kbd>Set</kbd> subtag with content must define a <kbd>type</kbd> attribute with <kbd>content|metadata</kbd> for value");
              $newMeta = new Metadata($this->context, $name, $type == 'content' ? type::content : type::metadata);
              Metadata::compile ($subTag->getChildren(), $newMeta);
              $newProps = [$name => $newMeta];
            }
            else $newProps = $subTag->props->getAll ();
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

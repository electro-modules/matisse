<?php
namespace Electro\Plugins\Matisse\Components;

use Electro\Plugins\Matisse\Components\Base\Component;
use Electro\Plugins\Matisse\Components\Base\HtmlComponent;
use Electro\Plugins\Matisse\Components\Internal\Metadata;
use Electro\Plugins\Matisse\Interfaces\PresetsInterface;
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

class Preset
{
  /** @var Component[] */
  public $content;
  /** @var string A regular expression. */
  public $matchClass;
  /** @var string A tag name. */
  public $matchTag;
  /** @var array A map. */
  public $props;

  /**
   * Preset constructor.
   *
   * @param string $rule A CSS-compatible selector, supporting tag and class names.
   */
  public function __construct ($rule)
  {
    $this->matchTag = str_extract ($rule, '/^[\w\-]+/');
    $class          = str_extract ($rule, '/^\.([\w\-]+)/');
    if ($class)
      $this->matchClass = sprintf ('/(?:^| )%s(?:$| )/', preg_quote ($class));
  }

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
 *     optional content to be appended to the target
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
 * >`tagName` | `.className` | `tagName.className`
 */
class Presets extends Component implements PresetsInterface
{
  const allowsChildren = true;

  const propertiesClass = PresetsProperties::class;

  /** @var PresetsProperties */
  public $props;

  /**
   * @var Preset[]
   */
  private $presets;

  function applyPresets (Component $component)
  {
    foreach ($this->presets as $preset) {
      if ($preset->matchTag && $preset->matchTag !== $component->getTagName ())
        continue;
      if ($preset->matchClass &&
          (!$component instanceof HtmlComponent || !preg_match ($preset->matchClass, $component->props->class))
      )
        continue;
      if ($preset->props)
        $component->props->applyDefaults ($preset->props);
      if ($preset->content) {
        Metadata::compile ($preset->content, $component);
        inspect ($component);
      }
    }
  }

  protected function render ()
  {
    $this->presets = [];
    /** @var Metadata $where */
    foreach ($this->props->where as $where) {
      $whereSubtags = $where->getChildren ();
      if (!$whereSubtags) continue;
      $where->databind ();
      $rule = $where->props->match;
      if (!$rule) continue;
      $preset = new Preset ($rule);
      if ($whereSubtags[0]->getTagName () == 'Set') {
        $Set = array_shift ($whereSubtags);
        $Set->databind ();
        $preset->props = $Set->props->getAll ();
      }
      $preset->content = $whereSubtags;
      $this->presets[] = $preset;
    }
    $stack                    = $this->context->presets; // Save a copy of the current presets stack.
    $this->context->presets[] = $this;
    $this->runChildren ();
    $this->context->presets = $stack; // Note: it is not safe to just do an array_pop() here.
  }

}

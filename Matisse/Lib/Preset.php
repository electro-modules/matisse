<?php
namespace Electro\Plugins\Matisse\Lib;

use Electro\Plugins\Matisse\Components\Base\Component;
use Electro\Plugins\Matisse\Components\Base\HtmlComponent;
use Electro\Plugins\Matisse\Components\Internal\Metadata;

class Preset
{
  /** @var Component[]|null */
  public $append = null;
  /** @var Component[]|null */
  public $content = null;
  /** @var Component[]|null */
  public $prepend = null;
  /** @var array|null A map. */
  public $props = null;
  /** @var string[]|null A list of property names. */
  public $unset = null;

  /** @var string A regular expression. Empty string = match any */
  private $matchClass = '';
  /** @var string Empty string = match any */
  private $matchPropName = '';
  /** @var string Empty string = match any non-empty value */
  private $matchPropValue = '';
  /** @var string A tag name. Empty string = match any */
  private $matchTag = '';

  /**
   * Preset constructor.
   *
   * @param string $selector A CSS-compatible selector, supporting tag and class names.
   */
  public function __construct ($selector)
  {
    $this->matchTag = str_extract ($selector, '/^[\w\-]+/');
    $classes = '';
    do {
      $class = str_extract ($selector, '/^\.([\w\-]+)/');
      if ($class)
        $classes .= sprintf ('(?=.*\b%s\b)', preg_quote ($class));
    }
    while ($class);
    $this->matchClass = $classes ? "/^$classes/" : '';
    $prop = str_extract ($selector, '/^\[([\w\-]+)/');
    if ($prop) {
      $this->matchPropName  = $prop;
      $this->matchPropValue = str_extract ($selector, '/^=([^\]]+)\]/');
    }
  }

  /**
   * Apply the preset to the given component.
   *
   * @param Component $component
   */
  function apply (Component $component)
  {
    if ($this->props)
      $component->props->applyDefaults ($this->props);
    if ($this->unset)
      foreach ($this->unset as $prop)
        unset ($component->props->$prop);
    if ($this->content) {
      $component->removeChildren();
      Metadata::compile ($this->content, $component);
    }
    if ($this->prepend)
      Metadata::compile ($this->prepend, $component, true);
    if ($this->append)
      Metadata::compile ($this->append, $component);
  }

  /**
   * If the given component matches the preset's selector, it applies the preset to it.
   *
   * @param Component $component
   * @return bool true if the preset was applied.
   */
  function ifMatchesApply (Component $component)
  {
    if ($this->matches ($component)) {
      $this->apply ($component);
      return true;
    }
    return false;
  }

  /**
   * Checks if the given component matches the preset's selector.
   *
   * @param Component $component
   * @return bool
   */
  function matches (Component $component)
  {
    if ($this->matchTag && $this->matchTag !== $component->getTagName ())
      return false;
    if ($this->matchClass &&
        (!$component instanceof HtmlComponent || !preg_match ($this->matchClass, $component->props->class))
    )
      return false;
    if ($component->supportsProperties () && ($prop = $this->matchPropName)) {
      if (!$component->props->defines ($prop))
        return false;
      if ($this->matchPropValue !== '') {
        if ($component->props->$prop != $this->matchPropValue)
          return false;
      }
      else if (!exists ($component->props->$prop))
        return false;
    }
    return true;
  }

}

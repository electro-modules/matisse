<?php
namespace Selenia\Matisse\Components\Base;

use Selenia\Matisse\Properties\Base\HtmlComponentProperties;

class HtmlComponent extends Component
{
  const propertiesClass = HtmlComponentProperties::class;
  /**
   * The component's runtime CSS classes.
   *
   * You should never change the `class` attribute at rendering time, because if the component
   * is being repeatedly re-rendered (being a child of a `<Repeat>` component, for instance), the
   * attribute will become unstable. Use this property instead, which is reset for every rendering of the component.
   *
   * @var string
   */
  public $cssClassName = '';
  /**
   * @var string[] A map of attribute names to attribute values,
   */
  public $htmlAttrs = [];
  /** @var HtmlComponentProperties */
  public $props;

  /**
   * Override to select a different tag as the component container.
   *
   * @var string
   */
  protected $containerTag = 'div';
  /**
   * Set this to define the preset cssClassName value for each repeated rendering of the component.
   *
   * @var string
   */
  protected $originalCssClassName = '';

  /**
   * @param string $class
   * @return $this
   */
  function addClass ($class)
  {
    $class = trim ($class);
    if ($class === '') return $this;
    $l = explode (' ', $this->cssClassName);
    $p = array_search ($class, $l);
    if ($p !== false) return $this;
    array_push ($l, $class);
    $this->cssClassName = implode (' ', $l);
    return $this;
  }

  function afterRender ()
  {
    $this->cssClassName = $this->originalCssClassName;
  }

  protected function postRender ()
  {
    $this->end ();
  }

  protected function preRender ()
  {
    $this->begin ($this->containerTag);
    $this->attr ('id', either ($this->props->containerId, $this->props->id));
    $this->attr ('class', enum (' ',
      rtrim ($this->className, '_'),
      $this->props->class,
      $this->cssClassName,
      $this->props->disabled ? 'disabled' : null
    ));
    if (!empty($this->props->htmlAttrs))
      echo ' ' . $this->props->htmlAttrs;
    if ($this->htmlAttrs)
      foreach ($this->htmlAttrs as $k => $v)
        echo " $k=\"" . htmlspecialchars ($v) . '"';
  }

  function setupFirstRun ()
  {
    if (!$this->originalCssClassName)
      $this->originalCssClassName = $this->cssClassName;
  }

}

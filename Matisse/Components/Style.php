<?php
namespace Selenia\Matisse\Components;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Properties\Base\ComponentProperties;
use Selenia\Matisse\Properties\TypeSystem\type;

class StyleProperties extends ComponentProperties
{
  /**
   * If set, allows inline stylesheet deduplication by ignoring Style instances with the same name as a previously run
   * Style.
   * > This only applies to inline styles, external stylesheets are always deduplicated.
   *
   * @var string
   */
  public $name = [type::id];
  /**
   * If set, the URL for an external CSS stylesheet.<br>
   * If not set, the tag content will be used as an inline stylesheet.
   *
   * @var string
   */
  public $src = '';
}

class Style extends Component
{
  const allowsChildren = true;
  
  const propertiesClass = StyleProperties::class;
  
  /** @var StyleProperties */
  public $props;

  /**
   * Registers a stylesheet on the Page.
   */
  protected function render ()
  {
    $prop = $this->props;
    if (exists ($prop->src))
      $this->context->getAssetsService ()->addStylesheet ($prop->src);
    else if ($this->hasChildren ())
      $this->context->getAssetsService ()->addInlineCss ($this, $prop->name);
  }
}


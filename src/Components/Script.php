<?php
namespace Matisse\Components;

use Matisse\Components\Base\Component;
use Matisse\Properties\Base\ComponentProperties;
use Matisse\Properties\TypeSystem\type;

class ScriptProperties extends ComponentProperties
{
  /**
   * If set, allows inline scripts deduplication by ignoring Script instances with the same name as a previously run
   * Script.
   * > This only applies to inline scripts, external scripts are always deduplicated.
   *
   * @var string
   */
  public $name = [type::id];
  /**
   * If set, the URL for an external script.<br>
   * If not set, the tag content will be used as an inline script.
   *
   * @var string
   */
  public $src = '';
  /**
   * @var bool
   */
  public $prepend = false;
}

class Script extends Component
{
  const allowsChildren = true;

  const propertiesClass = ScriptProperties::class;

  /** @var ScriptProperties */
  public $props;

  /**
   * Registers a script on the Page.
   */
  protected function render ()
  {
    $prop = $this->props;
    if (exists ($prop->src))
      $this->context->getAssetsService ()->addScript ($prop->src, $this->props->prepend);
    else if ($this->hasChildren ())
      $this->context->getAssetsService ()->addInlineScript ($this, $prop->name, $this->props->prepend);
  }
}


<?php

namespace Matisse\Components;

use Matisse\Components\Base\Component;
use Matisse\Properties\Base\ComponentProperties;
use Matisse\Properties\TypeSystem\type;

class ScriptProperties extends ComponentProperties
{
  /**
   * If set, the given value will be output as a javascript variable whose name is defined by the `to` property.
   *
   * @var mixed
   */
  public $export = [type::any, null];
  /**
   * If set, allows inline scripts deduplication by ignoring Script instances with the same name as a previously run
   * Script.
   * > This only applies to inline scripts, external scripts are always deduplicated.
   *
   * @var string
   */
  public $name = [type::id];
  /**
   * @var bool
   */
  public $prepend = false;
  /**
   * If set, the URL for an external script.<br>
   * If not set, the tag content will be used as an inline script.
   *
   * @var string
   */
  public $src = '';
  /**
   * If set, the javascript variable name that will receive the value exportyed by the `export` property.
   *
   * @var string
   */
  public $var = '';
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
    $prop   = $this->props;
    $assets = $this->context->getAssetsService ();

    if (exists ($prop->var))
      $assets->addInlineScript (sprintf ('var %s=%s;', $prop->var, javascriptLiteral ($prop->export, false)));

    elseif (exists ($prop->src))
      $assets->addScript ($prop->src, $this->props->prepend);

    elseif ($this->hasChildren ())
      $assets->addInlineScript (self::getRenderingOfSet ($this->getChildren ()), $prop->name, $this->props->prepend);
  }
}


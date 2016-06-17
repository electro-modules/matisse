<?php
namespace Electro\Plugins\Matisse\Components;

use Electro\Plugins\Matisse\Components\Base\HtmlComponent;
use Electro\Plugins\Matisse\Properties\Base\GenericProperties;

class GenericHtmlComponent extends HtmlComponent
{
  const propertiesClass = GenericProperties::class;

  /** @var GenericProperties */
  public $props;

  protected function postRender ()
  {
  }

  protected function preRender ()
  {
  }

  protected function render ()
  {
    $this->begin ($this->getTagName ());
    $attrs = $this->props->getAll ();
    foreach ($attrs as $k => $v) {
      if (isset($v) && $v !== '' && is_scalar ($v)) {
        if (is_bool ($v)) {
          if ($v) echo " $k";
        }
        else echo " $k=\"$v\"";
      }
    }
    $this->beginContent ();
    $this->runChildren ();
    $this->end ();
  }

}

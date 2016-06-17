<?php
namespace Electro\Plugins\Matisse\Components\Base;

use Electro\Plugins\Matisse\Properties\Base\HtmlContainerProperties;

class HtmlContainer extends HtmlComponent
{
  const propertiesClass = HtmlContainerProperties::class;

  public $defaultProperty = 'content';

  protected function render ()
  {
    $this->beginContent ();
    $this->runChildren ($this->hasChildren () ? null : $this->defaultProperty);
  }

}

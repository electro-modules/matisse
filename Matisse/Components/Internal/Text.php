<?php
namespace Electro\Plugins\Matisse\Components\Internal;

use Electro\Plugins\Matisse\Components\Base\Component;
use Electro\Plugins\Matisse\Parser\DocumentContext;
use Electro\Plugins\Matisse\Parser\Expression;
use Electro\Plugins\Matisse\Properties\Base\ComponentProperties;
use Electro\Plugins\Matisse\Properties\TypeSystem\type;

class TextProperties extends ComponentProperties
{
  public $value = ['', type::any];
}

final class Text extends Component
{
  const propertiesClass = TextProperties::class;
  /** @var TextProperties */
  public $props;

  public static function from (DocumentContext $context = null, $content = '')
  {
    assert (is_string ($content));
    $text = new static;
    $text->setup (null, $context, $content != '' ? ['value' => $content] : null);
    return $text;
  }

  protected function evalBinding (Expression $exp)
  {
    return _e (parent::evalBinding ($exp));
  }

  protected function render ()
  {
    echo $this->props->value;
  }

}

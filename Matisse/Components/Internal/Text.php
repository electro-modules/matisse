<?php
namespace Selenia\Matisse\Components\Internal;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Parser\DocumentContext;
use Selenia\Matisse\Parser\Expression;
use Selenia\Matisse\Properties\Base\ComponentProperties;
use Selenia\Matisse\Properties\TypeSystem\type;

class TextProperties extends ComponentProperties
{
  public $value = ['', type::any];
}

final class Text extends Component
{
  const propertiesClass = TextProperties::class;
  /** @var TextProperties */
  public $props;

  public function __construct (DocumentContext $context = null, $props = null)
  {
    parent::__construct ();
    if ($context)
      $this->setContext ($context);
    $this->setTagName ('Text');
    $this->setProps ($props);
  }

  public static function from (DocumentContext $context = null, $text)
  {
    return new Text($context, ['value' => $text]);
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

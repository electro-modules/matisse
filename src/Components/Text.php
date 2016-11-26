<?php
namespace Matisse\Components;

use Matisse\Components\Base\Component;
use Matisse\Parser\DocumentContext;
use Matisse\Parser\Expression;
use Matisse\Properties\Base\ComponentProperties;
use Matisse\Properties\TypeSystem\type;

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

  public function export ()
  {
    $o = parent::export ();
    // Replace the properties array by a shorter string, or remove it completely if no value is set
    unset ($o[MPROPS]);
    unset ($o[MPARENT]);
    if ($this->props->value)
      $o[0] = $this->props->value;
    return $o;
  }

  public function import ($a)
  {

    // Expand the string content into a properties array
    if (isset($a[0]))
      $a[MPROPS] = ['value' => $a[0]];
    parent::import ($a);
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

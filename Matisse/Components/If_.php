<?php
namespace Selenia\Matisse\Components;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Components\Internal\Metadata;
use Selenia\Matisse\Parser\Parser;
use Selenia\Matisse\Properties\Base\ComponentProperties;
use Selenia\Matisse\Properties\TypeSystem\type;

class IfProperties extends ComponentProperties
{
  /**
   * @var Metadata[]
   */
  public $case = type::collection;
  /**
   * @var Metadata|null
   */
  public $else = type::content;
  /**
   * @var string A regular expression.
   */
  public $matches = '';
  /**
   * @var bool Assigned via the `<If {exp}>` syntax.
   */
  public $nameless = type::bool;
  /**
   * > **Mote:** it doesn't work with databinding.
   *
   * @var bool
   */
  public $not = false;
  /**
   * @var mixed
   */
  public $value = type::any;
}

/**
 * Rendes content blocks conditionally.
 *
 * ##### Syntax:
 * ```
 * <If {exp}>
 *   content if truthy (not null nor an empty string)
 *   <Else> content if falsy </Else>
 * </If>
 *
 * <If value="value"> content if value is truthy </If>    // this is the same as <If {value}>
 *
 * <If not value="value"> content if value is falsy </If> // this is the same as <If {!value}>
 *
 * <If value="value" matches="regexp"> content if value matches the regular expression </If>
 *
 * <If value="value" not matches="regexp"> content if value doesn't match the regular expression </If>
 *
 * <If value="value">
 *   <Case is="value1"> content if value == value1 </Case>
 *   ...
 *   <Case is="valueN"> content if value == valueN </Case>
 *   <Else> content if no match </Else>
 * </If>
 * ```
 */
class If_ extends Component
{
  const allowsChildren = true;

  const propertiesClass = IfProperties::class;

  /** @var IfProperties */
  public $props;

  protected function evaluate ()
  {
    $prop = $this->props;
    $np   = Parser::NAMELESS_PROP;

    if (isset($prop->$np)) {
      if ($prop->$np)
//      if ($prop->getComputed($np))
        return $this->getChildren ();
      return $this->getChildren ('else');
    }

    $v   = $prop->get ('value');
    $not = $prop->not;

    if (exists ($prop->matches)) {
      if (preg_match ("%$prop->matches%", $v) xor $not)
        return $this->getChildren ();
      return $this->getChildren ('else');
    }

    if ($prop->case) {
      foreach ($prop->case as $param) {
        if ($v == $param->props->is)
          return $param->getChildren ();
      }
      return $this->getChildren ('else');
    }

    if (toBool ($v) xor $not)
      return $this->getChildren ();

    return $this->getChildren ('else');
  }

  protected function render ()
  {
    $result = $this->evaluate ();
    if ($result)
      self::renderSet ($result);
  }
}

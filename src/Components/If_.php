<?php
namespace Matisse\Components;

use Matisse\Components\Base\Component;
use Matisse\Parser\Parser;
use Matisse\Properties\Base\ComponentProperties;
use Matisse\Properties\TypeSystem\type;

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
   * A regular expression. Ex: `'/some .* apples/i'`
   *
   * @var string
   */
  public $matches = '';
  /**
   * @var bool Assigned via the `<If {exp}>` syntax.
   */
  public $nameless = type::any;
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
 * Renders content blocks conditionally.
 *
 * ##### Basic syntax:
 * ```
 * <If {exp}>
 *   content if truthy (not null nor an empty string)
 *   <Else>
 *     content if falsy (note: the Else block is optional)
 *   </Else>
 * </If>
 * ```
 * <p>`{exp}` is a databinding expression. Ex: `{some.prop == 5}`
 * <p>Note that when `{exp}` is a string value, it's considered `false` when its value is `'false'`, `'no'`, `'off'`,
 * `'0'` or `''`.
 *
 * ##### Other forms:
 * ```
 * <If value={exp}>                   This is the same as <If {exp}>
 *   content if exp is truthy         You can use either on the examples below.
 * </If>
 *
 * <If not {exp}>                     This is the same as <If {!exp}> or <If not value={exp}>
 *   content if exp is falsy
 * </If>
 *
 * <If {exp} matches="regexp">        This is the same as <If value={exp} matches="regexp">
 *   content if exp matches the regular expression
 * </If>
 *
 * <If {exp} not matches="regexp">    This is the same as <If value={exp} not matches="regexp">
 *   content if exp doesn't match the regular expression
 * </If>
 *
 * <If {exp}>
 *   <Case is=value1>
 *     content if exp == value1
 *   </Case>
 *   ...
 *   <Case is=valueN>
 *     content if exp == valueN
 *   </Case>
 *   <Else>
 *     content if no match (optional)
 *   </Else>
 * </If>
 * ```
 */
class If_ extends Component
{
  const allowsChildren = true;

  const propertiesClass = IfProperties::class;

  /** @var IfProperties */
  public $props;

  /**
   * Evaluates the condition of the If clause.
   *
   * @return bool
   */
  protected function evaluate ()
  {
    $prop = $this->props;
    $np   = Parser::NAMELESS_PROP;
    $v    = isset ($prop->$np) ? $prop->$np : $prop->get ('value');
    $not  = $prop->not;

    if (exists ($prop->matches))
      return (bool)preg_match ("%$prop->matches%", $v) xor $not;

    if ($prop->case) {
      foreach ($prop->case as $param) {
        if ($v == $param->props->get ('is')) // Note that we use the loose comparison operator == here.
          return true;
      }
      return false;
    }

    return strToBool ($v) xor $not;
  }

  protected function render ()
  {
    self::renderSet ($this->getChildren ($this->evaluate () ? null : 'else'));
  }

}

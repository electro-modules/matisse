<?php
namespace Selenia\Plugins\Matisse\Components\Macro;

use Selenia\Plugins\Matisse\Components\Base\Component;
use Selenia\Plugins\Matisse\Properties\Base\ComponentProperties;
use Selenia\Plugins\Matisse\Properties\TypeSystem\is;
use Selenia\Plugins\Matisse\Properties\TypeSystem\type;

class MacroParamProperties extends ComponentProperties
{
  /**
   * @var mixed
   */
  public $default = type::any;
  /**
   * @var string
   */
  public $name = [type::string, is::required];
}

/**
 * Provides default values dynamically for a property on the current scope's component propertues.
 */
class MacroParam extends Component
{
  const propertiesClass = MacroParamProperties::class;

  /** @var MacroParamProperties */
  public $props;

  protected function render ()
  {
    $prop       = $this->props;
    $scopeProps = $this->getDataBinder ()->getProps ();
    $name       = $prop->name;

    if (isset($scopeProps) && !exists ($scopeProps->$name))
      $scopeProps->$name = $prop->default;
  }

}

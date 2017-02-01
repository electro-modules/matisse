<?php

namespace Matisse\Components\Macro;

use Matisse\Components\Base\Component;
use Matisse\Properties\Base\ComponentProperties;
use Matisse\Properties\TypeSystem\is;
use Matisse\Properties\TypeSystem\type;

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
    $viewModel  = $this->getDataBinder ()->getViewModel ();
    $scopeProps =& $viewModel['props'];
    $name       = $this->props->name;

    if (isset($scopeProps) && !get ($scopeProps, $name))
      $scopeProps[$name] = $this->props->default;
  }

}

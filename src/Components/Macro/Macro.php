<?php

namespace Matisse\Components\Macro;

use Matisse\Components\Base\Component;
use Matisse\Properties\Macro\MacroProperties;

/**
 * The Macro component allows you to define a component via markup instead of PHP code.
 *
 * <p>A macro is composed by metadata elements and a template.
 * - With metadata you can define macro parameters, stylesheets and scripts.
 * - All child elements that are not metadata define a template that will be copied to the property whose name is set
 * by the `defaultParam` property.
 *
 * > A `MacroCall` is a component that can be represented via any tag that has the same name as the macro it refers
 * to. MacroCalls load and render the corresponding template.
 */
class Macro extends Component
{
  const allowsChildren = true;

  const propertiesClass = MacroProperties::class;

  /** @var MacroProperties */
  public $props;

  public function onParsingComplete ()
  {
    $this->props->name = normalizeTagName ($this->props->name);
  }

  protected function render ()
  {
    $this->runChildren ();
  }

}

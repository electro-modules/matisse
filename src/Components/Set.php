<?php

namespace Matisse\Components;

use Matisse\Components\Base\Component;
use Matisse\Properties\Base\MetadataProperties;

/**
 * Allows setting values on the view model, using markup on the template.
 */
class Set extends Component
{
  const propertiesClass = MetadataProperties::class;

  /** @var MetadataProperties */
  public $props;

  protected function render ()
  {
    $viewModel  = $this->getDataBinder ()->getViewModel ();
    $scopeProps =& $viewModel['props'];
    $props      = $this->props->getDynamic ();

    foreach ($props as $k => $v) {
      if ($k[0] == '@') {
        $k = substr ($k, 1);
        if (isset($scopeProps))
          $scopeProps[$k] = $v;
      }
      else $viewModel[$k] = $v;
    }
  }

}

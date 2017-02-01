<?php

namespace Matisse\Components;

use Matisse\Components\Base\Component;
use Matisse\Properties\Base\MetadataProperties;

/**
 * Provides default values dynamically for properties on view model.
 */
class Defaults extends Component
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
        if (isset($scopeProps) && empty(get ($scopeProps, $k)))
          $scopeProps[$k] = $v;
      }
      else if (empty(get ($viewModel, $k)))
        $viewModel[$k] = $v;
    }

  }

}

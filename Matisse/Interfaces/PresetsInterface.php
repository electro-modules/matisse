<?php
namespace Electro\Plugins\Matisse\Interfaces;

use Electro\Plugins\Matisse\Components\Base\Component;

interface PresetsInterface
{
  /**
   * @param Component $component The target component, just before it is rendered.
   */
  function applyPresets (Component $component);
}

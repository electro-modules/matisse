<?php
namespace Selenia\Plugins\Matisse\Interfaces;

use Selenia\Plugins\Matisse\Components\Base\Component;

interface PresetsInterface
{
  /**
   * @param Component $component The target component, just before it is rendered.
   */
  function applyPresets (Component $component);
}

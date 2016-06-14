<?php
namespace Selenia\Matisse\Interfaces;

use Selenia\Matisse\Components\Base\Component;

interface PresetsInterface
{
  /**
   * @param Component $component The target component, just before it is rendered.
   */
  function applyPresets (Component $component);
}

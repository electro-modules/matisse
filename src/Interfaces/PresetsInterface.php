<?php
namespace Matisse\Interfaces;

use Matisse\Components\Base\Component;

interface PresetsInterface
{
  /**
   * A listener callback invoked by all components before they are rendered.
   *
   * @param Component $component The target component, just before it is rendered.
   */
  function applyPresets (Component $component);
}

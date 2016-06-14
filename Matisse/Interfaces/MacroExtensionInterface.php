<?php
namespace Selenia\Matisse\Interfaces;

use Selenia\Matisse\Components\Macro\Macro;
use Selenia\Matisse\Components\Macro\MacroCall;

/**
 * Makes a component capable of performing macro transformations at template compile-time.
 */
interface MacroExtensionInterface
{
  /**
   * @param Macro     $macro
   * @param MacroCall $call
   * @param array     $compnents
   * @param int $index
   * @return bool
   */
  function onMacroApply (Macro $macro, MacroCall $call, array &$compnents, &$index);
}

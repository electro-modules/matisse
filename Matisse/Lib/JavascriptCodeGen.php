<?php
namespace Selenia\Matisse\Lib;

class JavascriptCodeGen
{
  /**
   * Generates a javascript representation of the provided options, stripping the ones that are null or empty strings.
   *
   * @param array  $options A map of options.
   * @param string $indent  Indentation level, composed of spaces.
   * @return string
   */
  static function makeOptions (array $options, $indent = '')
  {
    $o = mapAndFilter ($options, function ($v, $k) use ($indent) {
      return exists ($v) ? ("$k: " . var_export ($v, true)) : null;
    });
    return "{\n$indent  " . implode (",\n$indent  ", $o) . "\n$indent}";
  }

}

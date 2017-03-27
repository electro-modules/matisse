<?php
namespace Matisse\Lib;

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
      if (is_object ($v)) {
        if (method_exists ($v, 'toArray'))
          $v = $v->toArray ();
        else $v = (array)$v;
      }
      if (is_object ($v) && $v instanceof \RawText)
        return "$k: $v";
      return isset ($v)
        ? "$k: " . json_encode ($v, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        : null;
    });
    return "{\n$indent  " . implode (",\n$indent  ", $o) . "\n$indent}";
  }

}

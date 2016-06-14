<?php

/**
 * An array containing the names of the HTML tags which must not have a closing tag.
 *
 * @var array
 */
$VOID_ELEMENTS = [
  'area'    => 1,
  'base'    => 1,
  'br'      => 1,
  'col'     => 1,
  'command' => 1,
  'embed'   => 1,
  'hr'      => 1,
  'img'     => 1,
  'input'   => 1,
  'keygen'  => 1,
  'link'    => 1,
  'meta'    => 1,
  'param'   => 1,
  'source'  => 1,
  'track'   => 1,
  'wbr'     => 1,
];

/**
 * Creates an array representation of an html tag.
 *
 * @param string       $selector Syntax: 'tag#id.class1.class2...classN', all elements are optional. Default tag is
 *                               'div'.
 * @param array|string $attrs    Can also receive the value of $content, but BEWARE: the content array MUST have
 *                               integer keys, otherwise it will be interpreted as an attributes array.
 * @param array|string $content
 * @return array
 */
function h ($selector, $attrs = [], $content = [])
{
  $id      = str_extract ($selector, '/#([\w\-]*)/');
  $classes = str_extract ($selector, '/\.([\w\-\.]*)/');
  $tag     = $selector === '' ? 'div' : $selector;
  $classes = array_filter (explode ('.', $classes));

  if (is_string ($attrs)) {
    $content = $attrs;
    $attrs   = [];
  } // Check if $content is specified instead of $attrs and adjust arguments.
  else if (array_key_exists (0, $attrs)) { // supports having a null as the first array item.
    $content = $attrs;
    $attrs   = [];
  }
  if (isset($attrs['class'])) {
    $classes = array_merge ($classes, explode (' ', $attrs['class']));
    unset ($attrs['class']);
  }

  $outAttrs = [];
  if ($id)
    $outAttrs['id'] = $id;
  if ($classes)
    $outAttrs['class'] = implode (' ', $classes);
  // Put ID and CLASS attributes first.
  if ($outAttrs) $outAttrs = array_merge ($outAttrs, $attrs);
  else $outAttrs = $attrs;

  return [
    '<' => $tag,
    '@' => $outAttrs,
    '[' => $content,
  ];
}

/**
 * Render a tree-like structure into HTML.
 *
 * @param array|string|null $e
 * @param int               $d Depth.
 * @return string
 * @throws \InvalidArgumentException
 */
function html ($e, $d = 0)
{
  global $VOID_ELEMENTS;
  if (is_null ($e)) return '';
  if (is_string ($e)) return $e;
  if (isset($e['<'])) {
    $tag     = $e['<'];
    $attrs   = $e['@'];
    $content = $e['['];
    $s       = str_repeat (' ', $d);
    $o       = ($d ? "\n" : '') . "$s<$tag";
    foreach ($attrs as $k => $v) {
      if (is_null ($v)) continue;
      if (is_bool ($v)) {
        if ($v) $o .= " $k";
      }
      elseif (!is_scalar ($v))
        throw new \InvalidArgumentException("Non-scalar properties are not supported");
      else {
        $v = htmlspecialchars ($v);
        $o .= " $k=\"$v\"";
      }
    }
    $o .= ">";
    $c = isset($VOID_ELEMENTS[$tag]) ? '' : "</$tag>";
    if (empty($content))
      return "$o$c";
    $o .= html ($content, $d + 1);
    return substr ($o, -1) == '>' ? "$o\n$s$c" : "$o$c";
  }
  if (is_array ($e))
    return implode ('', map ($e, function ($v) use ($d) { return html ($v, $d + 1); }));
  throw new \InvalidArgumentException("Unsupported argument type for html(): " . gettype ($e));
}

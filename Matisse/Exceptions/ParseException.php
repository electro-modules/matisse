<?php
namespace Selenia\Matisse\Exceptions;

class ParseException extends MatisseException
{
  const CONTEXT_LENGTH = 200;

  public function __construct ($msg, $body = null, $start = null, $end = null)
  {
    $b = $start > self::CONTEXT_LENGTH ? $start - self::CONTEXT_LENGTH : 0;
    $e = $end + self::CONTEXT_LENGTH;
    if ($e >= strlen ($body)) $e = strlen ($body);
    if (isset($body)) {
      $tag   = substr ($body, $start, $end - $start + 1);
      $body  = substr_replace ($body, "<%$tag%>", $start, strlen ($tag));
      $lines = explode ("\n", substr ($body, $b, $e - $b));
      array_pop ($lines);
      array_shift ($lines);
      $code = implode ("\n", $lines);
      $code = htmlentities ($code, null, 'utf-8');
      $code = preg_replace ('/&lt;.*?&gt;/s', '<span class="tag">$0</span>', $code);
      $code = preg_replace ('/&lt;%(.*?)%&gt;/s', '<span class="tag-hilight">$1</span>', $code);
      $msg .= "<h4>Error location:</h4><code>$code</code>";
    }
    parent::__construct ($msg, 'Parsing error');
  }

}

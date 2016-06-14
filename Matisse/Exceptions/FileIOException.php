<?php
namespace Selenia\Matisse\Exceptions;

class FileIOException extends MatisseException
{
  public function __construct ($filename, $mode = 'read', array $paths = null)
  {
    switch ($mode) {
      case 'read':
        $m = "was not found";
        break;
      case 'write':
        $m = "can't be written to";
        break;
      case 'delete':
        $m = "can't be deleted";
        break;
      default:
        throw new \RuntimeException("Invalid mode $mode.");
    }
    $extra = $paths
      ? sprintf ("<p>The file was searched for at:<ul>%s</ul>", implode ('', map ($paths,
        function ($p) use ($filename) { return "<li><pre>$p/$filename</pre>"; }
      )))
      : '';
    parent::__construct ("File '<kbd>$filename</kbd>' $m.$extra");
  }

}

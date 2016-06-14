<?php
namespace Selenia\Matisse\Services;

use Selenia\Matisse\Components\Internal\DocumentFragment;
use Selenia\Matisse\Components\Macro\Macro;
use Selenia\Matisse\Exceptions\FileIOException;
use Selenia\Matisse\Exceptions\ParseException;
use Selenia\Matisse\Parser\DocumentContext;
use Selenia\Matisse\Parser\Parser;

/**
 * Manages macros loading, storage and retrieval.
 */
class MacrosService
{
  /**
   * @var string[]
   */
  public $macrosDirectories = [];
  /**
   * File extension of macro files.
   *
   * @var string
   */
  public $macrosExt = '.html';
  /**
   * A list of memorized macros for the current request.
   *
   * @var string[]
   */
  private $macros = [];
  /**
   * @var Macro
   */
  private $loadedMacro = null;

  function addMacro (Macro $macro)
  {
    $this->loadedMacro = $macro;
//    if (isset($this->macros[$name]))
//      throw new ParseException("Can't redefine the <kbd>$name</kbd> macro");
//    $this->macros[$name] = $macro;

    // Remove macro from its original location. It now lives on only as a detached template.
    $macro->remove ();
  }

  /**
   * @param string    $name
   * @param DocumentContext $context
   * @return Macro
   * @throws ParseException
   */
  function getMacro ($name, DocumentContext $context)
  {
    $content = get ($this->macros, $name);
    if (!$content) return null;
    $parser   = new Parser;
    $root = new DocumentFragment;
    $root->setContext ($context);
    $parser->parse ($content, $root);
    $macro = $this->loadedMacro;
    $this->loadedMacro = null;
    return $macro;
  }

  /**
   * Searches for a file defining a macro for the given tag name.
   *
   * @param string    $tagName
   * @param DocumentContext $context
   * @param string    $filename [optional] Outputs the filename that was searched for.
   * @return Macro
   * @throws FileIOException
   * @throws ParseException
   */
  function loadMacro ($tagName, DocumentContext $context, &$filename = null)
  {
    $tagName  = normalizeTagName ($tagName);
    $filename = $tagName . $this->macrosExt;
    $content  = $this->loadMacroFile ($filename);
    $this->macros[$tagName] = $content;
    return $this->getMacro ($tagName, $context);
  }

  private function loadMacroFile ($filename)
  {
    foreach ($this->macrosDirectories as $dir) {
      $f = loadFile ("$dir/$filename", false);
      if ($f) return $f;
    }
    throw new FileIOException($filename);
  }

}

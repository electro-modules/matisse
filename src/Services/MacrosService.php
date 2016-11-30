<?php
namespace Matisse\Services;

use Electro\Interfaces\Views\ViewServiceInterface;
use Matisse\Components\DocumentFragment;
use Matisse\Components\Macro\Macro;
use Matisse\Config\MatisseSettings;
use Matisse\Exceptions\FileIOException;
use Matisse\Exceptions\MatisseException;

/**
 * Manages macros loading, storage and retrieval.
 */
class MacrosService
{
  /**
   * @var MatisseSettings
   */
  private $matisseSettings;
  /**
   * @var ViewServiceInterface
   */
  private $viewService;

  public function __construct (ViewServiceInterface $viewService, MatisseSettings $matisseSettings)
  {
    $this->viewService = $viewService;
    $this->matisseSettings = $matisseSettings;
  }

  function findMacroFile ($tagName)
  {
    $tagName  = normalizeTagName ($tagName);
    $filename = $tagName . $this->matisseSettings->macrosExt ();
    foreach ($this->matisseSettings->getMacrosDirectories () as $dir) {
      $path = "$dir/$filename";
      if (file_exists ($path))
        return $path;
    }
    throw new FileIOException($filename);
  }

  /**
   * Loads and compiles the macro.
   *
   * <p>This method searches for a file defining a macro for the given tag name.
   * <p>It returns a DocumentFragment containing the macro as its first child.
   *
   * @param string $tagName
   * @param string $filename [optional] Outputs the filename that was searched for.
   * @return DocumentFragment
   * @throws MatisseException
   */
  function loadMacro ($tagName, &$filename = null)
  {
    $filename = $this->findMacroFile ($tagName);
    /** @var \Matisse\Components\DocumentFragment $doc */
    $doc = $this->loadMacroFile ($filename);
    $c   = $doc->getFirstChild ();
    if ($c instanceof Macro)
      return $doc;
    $filename = $this->findMacroFile ($filename);
    throw new MatisseException("File <path>$filename</path> doesn't define a macro called <kbd>$tagName</kbd> right at the beginning of the file");
  }

  private function loadMacroFile ($filename)
  {
    return $this->viewService->loadFromFile ($filename)->getCompiled ();
  }

}

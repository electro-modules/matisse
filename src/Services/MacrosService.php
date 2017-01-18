<?php

namespace Matisse\Services;

use Electro\Caching\Lib\FileSystemCache;
use Electro\Interfaces\Views\ViewServiceInterface;
use Matisse\Components\DocumentFragment;
use Matisse\Components\Macro\Macro;
use Matisse\Config\MatisseSettings;
use Matisse\Exceptions\ComponentException;
use Matisse\Exceptions\FileIOException;
use Matisse\Exceptions\MatisseException;
use Matisse\Properties\Base\ComponentProperties;
use Matisse\Properties\TypeSystem\type;

/**
 * Manages macros loading, storage and retrieval.
 */
class MacrosService
{
  /**
   * @var FileSystemCache
   */
  private $cache;
  /**
   * @var MatisseSettings
   */
  private $matisseSettings;
  /**
   * @var ViewServiceInterface
   */
  private $viewService;

  public function __construct (ViewServiceInterface $viewService, MatisseSettings $matisseSettings,
                               FileSystemCache $cache)
  {
    $this->viewService     = $viewService;
    $this->matisseSettings = $matisseSettings;
    $cache->setNamespace ('views/macros/props');
    $cache->setOptions (['dataIsCode' => true]);
    $this->cache = $cache;
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
   * Loads and compiles the macro, or retrieves it from the cache.
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
    throw new MatisseException("File <path>$filename</path> doesn't define a macro called <kbd>$tagName</kbd> right at the beginning of the file");
  }

  /**
   * Compiles (with caching) a class for the macro's properties and returns an instance of it.
   *
   * @param string $tagName
   * @param Macro  $macro
   * @param string $path The filesystem path of the macro's source file.
   * @return string The properties' class name.
   */
  function setupPropsClass ($tagName, Macro $macro, $path)
  {
    $propsClass = $tagName . 'MacroProps';
    if (!class_exists ($propsClass, false)) {
        $this->cache->get ("$path.php", function () use ($propsClass, $macro) {

        $baseClass = ComponentProperties::class;
        $typeClass = type::class;
        $propsStr  = "  public \$macro='';\n";
        foreach ($macro->props->param as $param) {
          $def  = $param->props->default;
          $type = $param->props->type;
          if (!defined ("$typeClass::$type"))
            throw new ComponentException($macro, "Invalid parameter type: <kbd>$type</kbd>");
          $typeVal = constant ("$typeClass::$type");
          $defVal  = '';
          if (exists ($def))
            switch ($type) {
              case 'string':
              case 'id':
              case 'any':
                $defVal = ",'$def'";
                break;
              case 'bool':
              case 'number':
                $defVal = ",$def";
                break;
            }
          $propsStr .= "  public \${$param->props->name}=['$typeVal'$defVal];\n";
        }
        $code = <<<PHP
<?php
class $propsClass extends $baseClass
{
$propsStr}
PHP;
        return $code;
      });
    }
    return $propsClass;
  }

  private function loadMacroFile ($filename)
  {
    return $this->viewService->loadFromFile ($filename)->getCompiled ();
  }


}

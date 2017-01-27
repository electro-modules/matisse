<?php

namespace Matisse\Services;

use Electro\Caching\Lib\FileSystemCache;
use Electro\Interfaces\Views\ViewServiceInterface;
use Matisse\Components\Macro\Macro;
use Matisse\Config\MatisseSettings;
use Matisse\Exceptions\ComponentException;
use Matisse\Exceptions\FileIOException;
use Matisse\Exceptions\MatisseException;
use Matisse\Parser\Expression;
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
    foreach ($this->matisseSettings->getMacrosDirectories () as $dir => $viewPath) {
      $path = "$dir/$filename";
      if (file_exists ($path))
        return $viewPath ? "$viewPath/$filename" : $filename;
    }
    inspect ($this->matisseSettings->getMacrosDirectories ());
    throw new FileIOException($filename);
  }

  /**
   * Loads and compiles the macro, or retrieves it from the cache.
   *
   * <p>This method returns a DocumentFragment containing the macro as its first child.
   *
   * @param string $filename The template's full file path.
   * @return Macro
   * @throws MatisseException
   */
//  function loadMacro ($filename)
//  {
//    /** @var \Matisse\Components\DocumentFragment $doc */
//    $doc = $this->loadMacroFile ($filename);
//    $c   = $doc->getFirstChild ();
//    if ($c instanceof Macro)
//      return $c;
//    throw new MatisseException("File <path>$filename</path> doesn't define a macro called <kbd>$tagName</kbd> right at the beginning of the file");
//  }

  /**
   * Compiles (with caching) a properties class for a macro.
   *
   * @param string   $propsClass The fully qualified class name of the macro properties class to compile.
   * @param string   $path       The filesystem path of the macro's source file.
   * @param callable $getMacro   A function that returns a macro instance. It will only be called if the properties
   *                             class is not yet cached.
   * @throws MatisseException
   */
  function setupMacroProperties ($propsClass, $path, callable $getMacro)
  {
    if (!$path)
      throw new MatisseException ("Invalid template path");

    $this->cache->get ("$path.php", function () use ($propsClass, $path, $getMacro) {
      $baseClass = ComponentProperties::class;
      $typeClass = type::class;
      $propsStr  = '';
      $bindings  = [];
      $macro     = $getMacro();
      foreach ($macro->props->param as $param) {
        $name = $param->props->name;
        $def  = $param->props->default;
        $type = $param->props->type;
        if (!defined ("$typeClass::$type"))
          throw new ComponentException ($macro, "Invalid parameter type: <kbd>$type</kbd>");
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
        $propsStr .= "  public \$$name=['$typeVal'$defVal];\n";
        /** @var Expression $exp */
        $exp = $param->getBinding ('default');
        if ($exp)
          $bindings[$name] = serialize ($exp);
      }

      $bindingsStr = $bindings ? sprintf ('  const bindings = %s;
', \PhpCode::dump ($bindings, 1)) : '';

      $defParam = $macro->props->defaultParam;
      $defParam = $defParam ? "  public \$defaultParam = '$defParam';
" : '';

      $code = <<<PHP
<?php
class $propsClass extends $baseClass
{
{$bindingsStr}{$defParam}$propsStr}
PHP;
      return $code;
    });
  }

//  private function loadMacroFile ($filename)
//  {
//    return $this->viewService->loadFromFile ($filename)->getCompiled ();
//  }

}

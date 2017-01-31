<?php

namespace Matisse\Services;

use Electro\Interfaces\Views\ViewServiceInterface;
use Matisse\Components\Macro\MacroCall;
use Matisse\Config\MatisseSettings;
use Matisse\Exceptions\ComponentException;
use Matisse\Exceptions\FileIOException;
use Matisse\Exceptions\MatisseException;
use Matisse\Lib\MacroPropertiesCache;
use Matisse\Parser\Expression;
use Matisse\Properties\Base\ComponentProperties;
use Matisse\Properties\TypeSystem\type;

/**
 * Manages macros loading, storage and retrieval.
 */
class MacrosService
{
  /**
   * @var \Matisse\Lib\MacroPropertiesCache
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
                               MacroPropertiesCache $cache)
  {
    $this->viewService     = $viewService;
    $this->matisseSettings = $matisseSettings;
    $this->cache           = $cache;
  }

  /**
   * Loads and compiles the macro, or retrieves it from the cache.
   *
   * <p>This method returns a MacroInstance composite component containing the macro as its shadow DOM.
   *
   * @param string $tagName
   * @return MacroCall
   * @throws FileIOException
   */
  function createMacroInstance ($tagName)
  {
    $propsClass       = $tagName . 'Properties';
    $path             = $this->findMacroFile ($tagName);
    $com              = new MacroCall;
    $com->propsClass  = $propsClass;
    $com->templateUrl = $path;
    return $com;
  }

  function findMacroFile ($tagName)
  {
    $tagName  = normalizeTagName ($tagName);
    $filename = $tagName . $this->matisseSettings->macrosExt ();
    foreach ($this->matisseSettings->getMacrosDirectories () as $dir => $viewPath) {
      $path = "$dir/$filename";
      if (file_exists ($path))
        return substr($path, strlen(getcwd()) + 1);
//        return $viewPath ? "$viewPath/$filename" : $filename;
    }
    throw new FileIOException($filename);
  }

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

    $this->cache->get ("$propsClass.php", function () use ($propsClass, $path, $getMacro) {
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

<?php
namespace Matisse\Components\Macro;

use Electro\ViewEngine\Lib\ViewModel;
use Matisse\Components\Base\Component;
use Matisse\Components\Metadata;
use Matisse\Exceptions\ComponentException;
use Matisse\Properties\Macro\MacroProperties;
use Matisse\Properties\TypeSystem\type;

/**
 * The Macro component allows you to define a macro trasformation via markup.
 *
 * <p>A macro is composed by metadata elements and a template.
 * - With metadata you can define macro parameters, stylesheets and scripts.
 * - All child elements that are not metadata define the template that will be transformed and replace a
 * {@see MacroCall} that refers to the Macro.
 *
 * > A `MacroCall` is a component that can be represented via any tag that has the same name as the macro it refers
 * to.
 */
class Macro extends Component
{
  /** Finds binding expressions which have macro parameter bindings. */
  const FIND_MACRO_EXP = '%
    \{
    [^\n\r]         # if line break then it is not an expression
    [^@\}]+         # not @ or }
    @               # if it is @ then the expression has a macro param ref.
    [^\}]+
    \}              # make sure the expression is closed
  %xu';
  /** Finds macro binding expressions. */
  const PARSE_SIMPLE_MACRO_BINDING_EXP = '%
    \{
    [^\n\r]         # if line break then it is not an expression
    \s*
    @               # must have a macro param ref.
    ([\w\-]*)       # capture the macro param name
    \s*
    (\| [^\}]* )?   # capture filters (if any)
    \}
  %xu';
  const allowsChildren                 = true;

  const propertiesClass = MacroProperties::class;

  /** @var MacroProperties */
  public $props;

  /**
   * Returns the macro parameter with the given name.
   *
   * ><p>**Note:** for use by MacroCall.
   *
   * @param string $name
   * @return Metadata|null null if not found.
   */
  public function getParameter ($name, &$found = false)
  {
    $params = $this->props->get ('param');
    if (!is_null ($params))
      foreach ($params as $param)
        if ($param->props->name == $name) {
          $found = true;
          return $param;
        }

    $found = false;
    return null;
  }

  /**
   * Gets a parameter's enumeration (if any).
   *
   * ><p>**Note:** for use by MacroCall.
   *
   * @param string $name Parameter name.
   * @return array|null null if no enumeration is defined.
   */
  public function getParameterEnum ($name)
  {
    $param = $this->getParameter ($name);
    if (isset($param)) {
      $enum = $param->props->get ('enum');
      if (exists ($enum))
        return explode (',', $enum);
    }
    return null;
  }

  /**
   * ><p>**Note:** for use by MacroCall.
   *
   * @param string $name
   * @return false|null|string
   * @throws ComponentException
   */
  public function getParameterType ($name)
  {
    $param = $this->getParameter ($name);
    if (isset($param)) {
      $p = type::getIdOf ($param->props->type);
      if ($p === false) {
        $s = join ('</kbd>, <kbd>', type::getAllNames ());
        throw new ComponentException($this,
          "The <kbd>$name</kbd> parameter has an invalid type: <kbd>{$param->props->type}</kbd>.<p>Expected values: <kbd>$s</kbd>.");
      }
      return $p;
    }
    return null;
  }

  /**
   * ><p>**Note:** for use by MacroCall.
   *
   * @return array|null
   */
  public function getParametersNames ()
  {
    $params = $this->props->get ('param');
    if (is_null ($params)) return null;
    $names = [];
    foreach ($params as $param)
      $names[] = lcfirst ($param->props->name);

    return $names;
  }

  /**
   * Called by MacroCall.
   *
   * @param ViewModel $viewModel
   * @throws ComponentException
   */
  public function importServices (ViewModel $viewModel)
  {
    $prop = $this->props->import;
    if (!$prop)
      return;
    $props    = $prop->props;
    $services = $props->service ?: $props->services;
    if ($services) {
      $injector = $this->context->injector;
      $aliases  = preg_split ('/\s+/', $services, -1, PREG_SPLIT_NO_EMPTY);

      if (exists ($as = $props->as)) {
        if (count ($aliases) > 1)
          throw new ComponentException ($this,
            "When using the <kbd>as</kbd> property, you can only specify one value for the <kbd>service</kbd> property");
        $service        = $injector->make ($services);
        $viewModel->$as = $service;
      }
      else {
        foreach ($aliases as $alias) {
          $service           = $injector->get ($alias);
          $viewModel->$alias = $service;
        }
      }
    }
  }

  public function onParsingComplete ()
  {
    $this->props->name = normalizeTagName ($this->props->name);
  }

  protected function init ()
  {
    parent::init ();
    $assets = $this->context->getAssetsService ();
    foreach ($this->props->style as $style) {
      if ($style->props->src)
        $assets->addStylesheet ($style->props->src);
      else $assets->addInlineCss ($style->runAndGetContent (), $style->props->name);
    }
  }

  protected function render ()
  {
    $this->run (true);
  }

}

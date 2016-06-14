<?php
namespace Selenia\Matisse\Properties\Macro;

use Selenia\Matisse\Components\Internal\Metadata;
use Selenia\Matisse\Components\Internal\Text;
use Selenia\Matisse\Components\Macro\Macro;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Properties\Base\MetadataProperties;
use Selenia\Matisse\Properties\TypeSystem\type;

class MacroCallProperties extends MetadataProperties
{
  /**
   * The name of the macro to be loaded at parse time and inserted on the current view, replacing the `MacroInstance`
   * component.
   *
   * > <p>You **can not** use databinding on this property, as the view model is not available at parse time.
   *
   * @var string
   */
  public $macro = '';
  /**
   * Points to the component that defines the macro for these properties.
   *
   * @var Macro
   */
  private $macroInstance;

  function defines ($name, $asSubtag = false)
  {
    if (property_exists ($this, $name))
      return true;
    if (!$this->macroInstance)
      $this->noMacro ();
    $this->macroInstance->getParameter ($name, $found);
    return $found;
  }

  /**
   * This is overriden so that default values can be correctly found.
   *
   * @param string $name
   * @param mixed  $default [optional] If set, it takes precedence over the parameter's default value.
   * @return mixed
   */
  function get ($name, $default = null)
  {
    if (property_exists ($this, $name))
      return $this->$name;
    if (array_key_exists ($name, $this->props))
      return $this->props [$name];

    if (isset($default)) return $default;
    return $this->getDefaultValue ($name);
  }

  function getAll ()
  {
    $names = $this->getPropertyNames ();
    return map ($names, function ($v, &$k) {
      $k = $v;
      return $this->$k;
    });
  }

  function getDefaultValue ($name)
  {
    if (!$this->macroInstance)
      $this->noMacro ();
    $param = $this->macroInstance->getParameter ($name, $found);
    if (!$found)
      throw new ComponentException ($this->component, "Undefined macro parameter <kbd>$name</kbd>.
<p>Available parameters: <b>" . implode (', ', $this->component->props->getPropertyNames ()) . '</b>');

    $v = $param->getComputedPropValue ('default');
    if ($param->props->type == 'content') {
      $meta = new Metadata ($this->macroInstance->context, ucfirst ($name), type::content);
      $meta->attachTo ($this->component);
      $meta->addChild (Text::from ($this->macroInstance->context, $v));
      return $meta;
    }
    return $v;
  }

  function getEnumOf ($propName)
  {
    if (!$this->macroInstance) $this->noMacro ();
    return $this->macroInstance->getParameterEnum ($propName) ?: [];
  }

  function getPropertyNames ()
  {
    if (!$this->macroInstance) $this->noMacro ();
    return $this->macroInstance->getParametersNames ();
  }

  function getTypeOf ($propName)
  {
    if (!$this->macroInstance) $this->noMacro ();
    return $this->macroInstance->getParameterType ($propName);
  }

  function isEnum ($propName)
  {
    if (!$this->macroInstance) $this->noMacro ();
    return !is_null ($this->macroInstance->getParameterEnum ($propName));
  }

  function isModified ($propName)
  {
    return array_key_exists ($propName, $this->props);
  }

  /**
   * Sets the component that defines the macro for these properties.
   * > This is used by {@see MacroInstance} when it creates an instance of this class.
   *
   * @param Macro $macro
   */
  function setMacro (Macro $macro)
  {
    $this->macroInstance = $macro;
  }

  private function noMacro ()
  {
    throw new ComponentException($this->component,
      "Can't access any of a macro instance's properties before a macro is assigned to it");
  }
}

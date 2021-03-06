<?php

namespace Matisse\Components\Macro;

use Matisse\Components\Base\CompositeComponent;
use Matisse\Components\Metadata;
use Matisse\Exceptions\ComponentException;
use Matisse\Exceptions\MatisseException;
use Matisse\Exceptions\ReflectionPropertyException;
use Matisse\Properties\TypeSystem\type;

/**
 * A `MacroCall` is a component that can be represented via any tag that has the same name as the macro it refers to.
 */
class MacroCall extends CompositeComponent
{
  const TAG_NAME        = 'Call';
  const allowsChildren  = true;
  const propertiesClass = null; // use dynamic properties class

  /** @var string */
  public $propsClass;

  public function export ()
  {
    $a = parent::export ();

    if ($this->propsClass)
      $a['@propsClass'] = $this->propsClass;

    return $a;
  }

  public function import ($a)
  {
    if (isset($a['@propsClass']))
      $this->propsClass = $a['@propsClass'];

    parent::import ($a);
  }

  /**
   * Move children (if any) to the default property.
   * This is done only once, when the component is parsed. Subsequent reads from the cache will create the component
   * with the children already placed on the correct property.
   *
   * @throws ComponentException
   */
  function onParsingComplete ()
  {
    // Validate defaultParam's value.
    $def = isset($this->props->defaultParam) ? $this->props->defaultParam : null;
    if (!empty($def)) {
      if (!$this->props->defines ($def))
        throw new ComponentException($this,
          "Invalid value for <kbd>defaultParam</kbd> on <b>&lt;{$this->getTagName()}></b>; parameter <kbd>$def</kbd> does not exist");

      // Move children to default parameter.
      if ($this->hasChildren ()) {
        $type = $this->props->getTypeOf ($def);
        if ($type != type::content && $type != type::metadata)
          throw new ComponentException($this, sprintf (
            "The macro's default parameter <kbd>$def</kbd> can't hold content because its type is <kbd>%s</kbd>.",
            type::getNameOf ($type)));

        $param = new Metadata($this->context, ucfirst ($def), $type);
        $this->props->set ($def, $param);
        $param->attachTo ($this);
        $param->setChildren ($this->getChildren ());
      }
    }
    elseif ($this->hasChildren ())
      throw new ComponentException ($this,
        'You may not specify content for this tag because it has no default property');
  }

  /**
   * Creates the properties object from the class generated for this macro type and copies the property values to it.
   *
   * If no properties class is generated yet, this method will compile it.
   *
   * @param array|null $props
   * @throws ComponentException
   * @throws MatisseException
   * @throws ReflectionPropertyException
   */
  function setProps (array $props = null)
  {
    if ($this->propsClass) {
      if (!class_exists ($this->propsClass, false)) {
        $this->context
          ->getMacrosService ()
          ->setupMacroProperties ($this->propsClass, $this->templateUrl, function () {
            $this->createView ();
            // Return the Macro instance.
            return $this->getShadowDOM ()->getFirstChild ();
          });
      }
      $this->props = new $this->propsClass ($this);
      if ($props)
        $this->props->apply ($props);
    }
    elseif ($props)
      throw new ComponentException($this, 'This component does not support properties.');
  }

  function supportsProperties ()
  {
    return isset($this->propsClass);
  }

  /**
   * Extend the default binding procedure by also incorporating bindings for computed default property values.
   *
   * <p>Binding will be applied as default values, which means that they will be set only for properties that have no
   * defined value.
   *
   * @throws ComponentException
   */
  protected function databind ()
  {
    $bp = "$this->propsClass::bindings";
    if (defined ($bp))
      foreach (constant ($bp) as $k => $v) {
        if (!exists($this->getComputedPropValue ($k)))
          $this->bindings[$k] = unserialize ($v);
      }
    parent::databind ();
  }

}

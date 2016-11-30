<?php
namespace Matisse\Components\Macro;

use Electro\ViewEngine\Lib\ViewModel;
use Matisse\Components\Base\Component;
use Matisse\Components\Base\CompositeComponent;
use Matisse\Components\Metadata;
use Matisse\Exceptions\ComponentException;
use Matisse\Exceptions\FileIOException;
use Matisse\Properties\Macro\MacroCallProperties;
use Matisse\Properties\TypeSystem\type;

/**
 * A `MacroCall` is a component that can be represented via any tag that has the same name as the macro it refers to.
 */
class MacroCall extends CompositeComponent
{
  const TAG_NAME        = 'Call';
  const allowsChildren  = true;
  const propertiesClass = MacroCallProperties::class;
  /** @var MacroCallProperties */
  public $props;
  /** @var Macro Points to the component that defines the macro for this instance. */
  protected $macroInstance;

  function render ()
  {
    // Validate defaultParam's value.
    $def = $this->getDefaultParam ();
    if (!empty($def)) {
      if (!$this->props->defines ($def))
        throw new ComponentException($this,
          "Invalid value for <kbd>defaultParam</kbd> on <b>&lt;{$this->props->macro}></b>; parameter <kbd>$def</kbd> does not exist");

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

    parent::render ();
  }

  protected function getDefaultParam ()
  {
    return $this->macroInstance->props->defaultParam;
  }

  /**
   * Loads the macro with the name specified by the `macro` property.
   *
   * @param array|null $props
   * @param Component  $parent
   */
  protected function onCreate (array $props = null, Component $parent = null)
  {
//    inspect("Macro: ".get ($props, 'macro'));
//    $z = get_object_vars($this);
//    foreach ($z as &$v)
//      $v = is_object($v) ? 'ID='.Debug::objectId($v) : $v;
//    inspect ($z);

    $this->parent = $parent;
    $name         = get ($props, 'macro');
    if (exists ($name)) {
      try {
        $frag = $this->context->getMacrosService ()->loadMacro ($name, $path);
        $this->macroInstance = $frag->getFirstChild ();
        $this->props->setMacro ($this->macroInstance);
        $this->setShadowDOM ($this->macroInstance);
      }
      catch (FileIOException $e) {
        /** @noinspection PhpUndefinedVariableInspection */
        self::throwUnknownComponent ($this->context, $name, $parent, $filename);
      }
    }
    parent::onCreate ($props, $parent);
  }

//  protected function setupViewModel ()
//  {
//    parent::setupViewModel ();
//    foreach ($this->props->getPropertiesOf (type::content, type::metadata, type::collection) as $prop => $v)
//      $this->props->$prop->preRun();
//  }
  protected function viewModel (ViewModel $viewModel)
  {
    parent::viewModel ($viewModel);
    // Import the container's model (if any) to the macro's view model
    $viewModel->model = $this->context->getDataBinder ()->getViewModel ()->model;
    $this->macroInstance->importServices ($viewModel);
  }


}

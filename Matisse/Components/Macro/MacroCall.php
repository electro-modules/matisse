<?php
namespace Electro\Plugins\Matisse\Components\Macro;

use Electro\Plugins\Matisse\Components\Base\Component;
use Electro\Plugins\Matisse\Components\Base\CompositeComponent;
use Electro\Plugins\Matisse\Components\Internal\DocumentFragment;
use Electro\Plugins\Matisse\Components\Internal\Metadata;
use Electro\Plugins\Matisse\Exceptions\ComponentException;
use Electro\Plugins\Matisse\Exceptions\FileIOException;
use Electro\Plugins\Matisse\Properties\Macro\MacroCallProperties;
use Electro\Plugins\Matisse\Properties\TypeSystem\type;
use Electro\ViewEngine\Lib\ViewModel;

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
  /**
   * Points to the component that defines the macro for this instance.
   *
   * @var Macro
   */
  protected $macroInstance;

  function onParsingComplete ()
  {
    // Move children to default parameter.

    if ($this->hasChildren ()) {
      $def = $this->getDefaultParam ();
      if (!empty($def)) {
        if (!$this->props->defines ($def))
          throw new ComponentException($this, "Invalid default property <kbd>$def</kbd>");

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
      else throw new ComponentException ($this,
        'You may not specify content for this tag because it has no default property');
    }
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
    $this->parent = $parent;
    $name         = get ($props, 'macro');
    if (exists ($name)) {
      $doc           = new DocumentFragment;
      $shadowContext = $this->context->makeSubcontext ();
      $doc->setContext ($shadowContext);
      $macro = $this->context->getMacrosService ()->getMacro ($name, $shadowContext);
      if (is_null ($macro))
        try {
          // A macro with the given name is not defined yet.
          // Try to load it now.
          $macro = $this->context->getMacrosService ()->loadMacro ($name, $shadowContext, $filename);
        }
        catch (FileIOException $e) {
          /** @noinspection PhpUndefinedVariableInspection */
          self::throwUnknownComponent ($this->context, $name, $parent, $filename);
        }
      $this->macroInstance = $macro;
      if (isset($this->props))
        $this->props->setMacro ($macro);
      $this->setShadowDOM ($doc); // this also attaches it, which MUST be done before adding children!
      $doc->addChildren ($macro->getChildren ());
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
    $viewModel->model = $this->parent->getViewModel()->model;
    $this->macroInstance->importServices ($viewModel);
  }


}

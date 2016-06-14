<?php
namespace Selenia\Matisse\Components\Base;

use Selenia\Interfaces\RenderableInterface;
use Selenia\Matisse\Debug\ComponentInspector;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Interfaces\PresetsInterface;
use Selenia\Matisse\Parser\DocumentContext;
use Selenia\Matisse\Properties\Base\AbstractProperties;
use Selenia\Matisse\Traits\Component\DataBindingTrait;
use Selenia\Matisse\Traits\Component\DOMNodeTrait;
use Selenia\Matisse\Traits\Component\MarkupBuilderTrait;
use Selenia\Matisse\Traits\Component\RenderingTrait;

/**
 * The base class from which all components derive.
 */
abstract class Component implements RenderableInterface
{
  use MarkupBuilderTrait, DataBindingTrait, DOMNodeTrait, RenderingTrait;

  const ERR_NO_CONTEXT = "<h4>Rendering context not set</h4>The component was not initialized correctly.";
  /**
   * Can this component have children?
   *
   * @var bool
   */
  const allowsChildren = false;
  /**
   * @var string|null Null if the component does not supports properties.
   */
  const propertiesClass = null;
  /**
   * An array containing the instance creation counters for each component class name.
   *
   * @var array
   */
  static protected $uniqueIDs = [];
  /**
   * The component's PHP class name.
   *
   * @var string
   */
  public $className;
  /**
   * The rendering context for the current request.
   *
   * @var DocumentContext
   */
  public $context;
  /**
   * When TRUE indicates that the component will not be rendered.
   *
   * @var boolean
   */
  public $hidden = false;
  /**
   * The component's published properties (the ones which are settable through html attribute declarations on the source
   * markup). This property contains an object of class ComponentAttributes or of a subclass of it, depending on the
   * component class of the instance.
   *
   * @var AbstractProperties
   */
  public $props;
  /**
   * Set to true on a component class definition to automatically assign an ID to instances.
   *
   * @see setAutoId().
   * @var bool
   */
  protected $autoId = false;
  /**
   * How many times has this instance been rendered.
   *
   * <p>It's useful for determining if the component is being repeated, for instance.
   *
   * @var int
   */
  protected $renderCount = 0;
  /**
   * When true, forces generation of a new auto-id, event if the component already has an assigned id.
   *
   * @var bool
   */
  private $regenerateId = false;
  /**
   * Cache for getTagName()
   *
   * @var string
   */
  private $tagName;

  /**
   * Creates a new component instance
   *
   * > <p>It is recommended to create component instances via {@see Component::create()} to correctly initialize them.
   */
  function __construct ()
  {
    $class           = get_class ($this);
    $s               = explode ('\\', $class);
    $this->className = end ($s);
    if ($this->supportsProperties ()) {
      $propClass   = $class::propertiesClass;
      $this->props = new $propClass ($this);
    }
  }

  /**
   * Creates and renders a component inline.
   *
   * @param Component  $parent
   * @param array|null $props
   * @param array|null $bindings
   * @return string The rendered output.
   */
  static function _ (Component $parent, array $props = null, array $bindings = null)
  {
    return static::create ($parent, $props, $bindings)->getRendering ();
  }

  /**
   * Creates a component instance of the static class where this method was invoked on.
   *
   * > This method does not support components that require constructor injection.
   *
   * @param Component  $parent   The component's container component.
   * @param string[]   $props    A map of property names to property values.
   *                             Properties specified via this argument come only from markup attributes, not
   *                             from subtags.
   * @param array|null $bindings A map of attribute names and corresponding databinding expressions.
   * @return Component Component instance.
   * @throws ComponentException
   */
  static function create (Component $parent, array $props = null, array $bindings = null)
  {
    return (new static)->setup ($parent, $parent->context, $props, $bindings);
  }

  static function throwUnknownComponent (DocumentContext $context, $tagName, Component $parent, $filename = null)
  {
    $paths    = implode ('', map ($context->getMacrosService ()->macrosDirectories,
      function ($dir) { return "<li><path>$dir</path></li>"; }
    ));
    $filename = $filename ? "<kbd>$filename</kbd>" : "it";
    throw new ComponentException (null,
      "<h3>Unknown component / macro: <b>$tagName</b></h3>
<p>Neither a <b>class</b>, nor a <b>property</b>, nor a <b>macro</b> with the specified name were found.
<p>If it's a component, perhaps you forgot to register the tag...
<p>If it's a macro, Matisse is searching for $filename on these paths:<ul>$paths</ul>
<table>
  <th>Container component:<td><b>&lt;{$parent->getTagName()}></b>, of type <b>{$parent->className}</b>
</table>
");
  }

  function __debugInfo ()
  {
    $props = object_publicProps ($this);
    unset ($props['parent']);
    unset ($props['context']);
    unset ($props['children']);
    return $props;
  }

  function __get ($name)
  {
    throw new ComponentException($this, "Can't read from non existing property <b>$name</b>.");
  }

  function __set ($name, $value)
  {
    throw new ComponentException($this, $this->props
      ? "Can't set non-existing (or non-accessible) property <b>$name</b>."
      : "Can't set properties on a component that doesn't support them."
    );
  }

  function __toString ()
  {
    try {
      return $this->inspect ();
    }
    catch (\Exception $e) {
      inspect ($e->getTraceAsString ());
      return '';
    }
  }

  function applyPresetsOnSelf ()
  {
    if ($this->context)
      foreach ($this->context->presets as $preset)
        if ($preset instanceof PresetsInterface)
          $preset->applyPresets ($this);
        elseif (method_exists ($preset, $this->className))
          $preset->{$this->className} ($this);
  }

  function getContextClass ()
  {
    return DocumentContext::class;
  }

  /**
   * Returns name of the tag that represents the component.
   * If the name is not set then it generates it from the class name and caches it.
   *
   * @return string
   */
  function getTagName ()
  {
    if (isset($this->tagName))
      return $this->tagName;
    preg_match_all ('#[A-Z][a-z]*#', $this->className, $matches, PREG_PATTERN_ORDER);

    return $this->tagName = ucfirst (strtolower (implode ('', $matches[0])));
  }

  /**
   * Sets the name of the tag that represents the component.
   * This is usually done by the parser, to increase the performance of getTagName().
   *
   * @param string $name
   */
  function setTagName ($name)
  {
    $this->tagName = $name;
  }

  function inspect ($deep = false)
  {
    return ComponentInspector::inspect ($this, $deep);
  }

  /**
   * Called after the component has been created by the parsing process
   * and all attributes and children have also been parsed.
   * Override this to implement parsing-time behavior.
   */
  function onParsingComplete ()
  {
    //implementation is specific to each component type.
  }

  function setContext ($context)
  {
    $this->context = $context;
  }

  /**
   * Initializes a newly created component with the given properties, if any.
   *
   * > **Warning:** for some components this method will not be called (ex: Literal).
   *
   * @param array|null $props A map of the component instance's properties being applied.
   * @throws ComponentException
   */
  function setProps (array $props = null)
  {
    if ($this->supportsProperties ()) {
      if ($props)
        $this->props->apply ($props);
    }
    else if ($props)
      throw new ComponentException($this, 'This component does not support properties.');
  }

  /**
   * Initializes a component right after instantiation.
   *
   * <p>**Note:** this method may not be called on some circumstances, for ex, if the component is rendered from a
   * middleware stack.
   *
   * @param Component|null                $parent   The component's container component (if any).
   * @param DocumentContext               $context  A rendering context.
   * @param array|AbstractProperties|null $props    A map of property names to property values.
   *                                                Properties specified via this argument come only from markup
   *                                                attributes, not from subtags.
   * @param array|null                    $bindings A map of attribute names and corresponding databinding
   *                                                expressions.
   * @return Component Component instance.
   * @throws ComponentException
   */
  function setup (Component $parent = null, DocumentContext $context, $props = null, array $bindings = null)
  {
    if (is_object ($props)) {
      $this->props = $props;
      $props       = [];
    }
    $this->setContext ($context);
    $this->bindings = $bindings;
    $this->onCreate ($props, $parent);
    $this->setProps ($props);
    $this->init ();
    return $this;
  }

  /**
   * Indicates if the component supports properties set via markup, which are represented by a properties object.
   *
   * @return bool
   */
  function supportsProperties ()
  {
    return (bool)static::propertiesClass;
  }

  protected function getUniqueId ()
  {
    if (array_key_exists ($this->className, self::$uniqueIDs))
      return ++self::$uniqueIDs[$this->className];
    self::$uniqueIDs[$this->className] = 1;

    return 1;
  }

  /**
   * Allows a component to perform additional initialization after being created. At that stage, the containing
   * document (or document fragment) rendering has not yet began.
   *
   * <p>**Note:** you **SHOULD** call the parent method when overriding this.
   *
   * > <p>**Tip:** override this method on a component subclass to set its script and stylesheet dependencies, so that
   * they are set before the page begins rendering.
   */
  protected function init ()
  {
    if ($this->autoId)
      $this->setAutoId ();
  }

  /**
   * @return bool Returns false if the component's rendering is disabled via the `hidden` property.
   */
  protected function isVisible ()
  {
    return !$this->hidden && (!isset($this->props) || !isset($this->props->hidden) || !$this->props->hidden);
  }

  /**
   * Allows a component to do something before it is initialized.
   * <p>The provided arguments have an informational purpose.
   *
   * @param array|null     $props
   * @param Component|null $parent
   */
  protected function onCreate (array $props = null, Component $parent = null)
  {
    //noop
  }

  /**
   * Do not call this. Set {@see autoId} instead.
   *
   * @return int New component ID.
   */
  protected function setAutoId ()
  {
    if ($this->regenerateId || (isset($this->props) && !property ($this->props, 'id'))) {
      $this->regenerateId = true; // if the component is re-rendered, always generate an id from now on.
      // Strip non alpha-numeric chars from generated name.
      $this->props->id =
        preg_replace ('/\W/', '', property ($this->props, 'name') ?: lcfirst ($this->className)) .
        $this->getUniqueId ();
    }

    return $this->props->id;
  }

}

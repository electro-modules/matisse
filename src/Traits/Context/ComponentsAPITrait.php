<?php

namespace Matisse\Traits\Context;

use Auryn\InjectionException;
use Electro\Interfaces\DI\InjectorInterface;
use Matisse\Components;
use Matisse\Components\Base\Component;
use Matisse\Components\GenericHtmlComponent;
use Matisse\Components\Macro\MacroCall;
use Matisse\Exceptions\ComponentException;
use Matisse\Exceptions\FileIOException;
use Matisse\Exceptions\MatisseException;
use Matisse\Exceptions\ReflectionPropertyException;
use Matisse\Parser\DocumentContext;
use Matisse\Properties\Base\AbstractProperties;

/**
 * Manages components on a rendering Context.
 *
 * @property InjectorInterface injector
 */
trait ComponentsAPITrait
{
  /**
   * A map of tag names to fully qualified PHP component class names.
   * It is initialized to the core Matisse components that can be instantiated via tags.
   *
   * @var array string => string
   */
  private static $coreTags = [
    'Presets'           => Components\Presets::class,
    'AssetsGroup'       => Components\AssetsGroup::class,
    'Channel'           => Components\Channel::class,
    'Content'           => Components\Content::class,
    'Fetchable'         => Components\Fetchable::class,
    'If'                => Components\If_::class,
    'Include'           => Components\Include_::class,
    'Macro'             => Components\Macro\Macro::class,
    'Defaults'          => Components\Defaults::class,
    'Script'            => Components\Script::class,
    'Set'               => Components\Set::class,
    'Style'             => Components\Style::class,
    'For'               => Components\For_::class,
    'Import'            => Components\Import::class,
    'FlashMessage'      => Components\FlashMessage::class,
    MacroCall::TAG_NAME => MacroCall::class,
  ];

  /**
   * A map of tag names to fully qualified PHP class names.
   *
   * @var array string => string
   */
  private $tags;

  /**
   * Creates an injectable component instance of the given class.
   *
   * @param string                        $class    Class name of the component to be created.
   * @param Component                     $parent   The component's container component (if any).
   * @param array|AbstractProperties|null $props    A map of property names to property values.
   *                                                Properties specified via this argument come only from markup
   *                                                attributes, not from subtags.
   * @param array|null                    $bindings A map of attribute names and corresponding databinding
   *                                                expressions.
   * @return Component
   * @throws ComponentException
   * @throws InjectionException
   * @throws ReflectionPropertyException
   */
  function createComponent ($class, Component $parent, $props = null, array $bindings = null)
  {
    /** @var Component $component */
    $component = $this->injector->make ($class);
    /** @var DocumentContext $this */
    return $component->setup ($parent, $this, $props, $bindings);
  }

  /**
   * Creates a component corresponding to the specified tag and optionally sets its published properties.
   *
   * <p>This is called by the parser.
   *
   * @param string     $tagName  It may contain an XML namespace prefix, ex: 'x:tag'
   * @param Component  $parent   The component's container component.
   * @param string[]   $props    A map of property names to property values.
   *                             Properties specified via this argument come only from markup attributes, not
   *                             from subtags.
   * @param array|null $bindings A map of attribute names and corresponding databinding expressions.
   * @param bool       $generic  If true, an instance of GenericComponent is created.
   * @param boolean    $strict   If true, failure to find a component class will throw an exception.
   *                             If false, an attempt is made to load a macro with the same name,
   * @return Component Component instance. For macros, an instance of Macro is returned.
   * @throws ComponentException
   * @throws InjectionException
   * @throws MatisseException
   * @throws ReflectionPropertyException
   * @throws FileIOException
   */
  function createComponentFromTag ($tagName, Component $parent, array $props = null, array $bindings = null,
                                   $generic = false, $strict = false)
  {
    $s = explode (':', $tagName, 2);
    if (count ($s) > 1)
      list ($prefix, $tagName) = $s;
    else $prefix = '';
    if (is_null ($props))
      $props = [];

    if ($prefix)
      throw new MatisseException ("XML namespaces are not yet supported.<p>Tag: <kbd>&lt;<b>$prefix:</b>$tagName&gt;</kbd>");

    if ($generic) {
      $component = new GenericHtmlComponent($tagName, $props);
      return $component;
    }
    $class = $this->getClassForTag ($tagName);
    /** @var DocumentContext $this */
    if (!$class) {
      if ($strict)
        Component::throwUnknownComponent ($this, $tagName, $parent);

      // Component class not found.
      // Convert the tag to a MacroInstance component instance that will attempt to load a macro with the same
      // name as the tag name.

      $component = $this->macrosService->createMacroInstance ($tagName);
    }

    // Component class was found.

    else $component = $this->injector->make ($class);

    // For both types of components:

    if (!$component instanceof Component)
      throw new ComponentException (null,
        sprintf ("Class <kbd>%s</kbd> is not a subclass of <kbd>Component</kbd>", get_class ($component)));

    $component->setTagName ($tagName);
    return $component->setup ($parent, $this, $props, $bindings);
  }

  /**
   * Retrieves the name of the PHP class that implements the component for a given tag.
   *
   * @param string $tag
   * @return string
   */
  function getClassForTag ($tag)
  {
    return get ($this->tags, $tag);
  }

  /**
   * Adds additional tag to PHP class mappings to the context.
   *
   * @param array $tags
   */
  function registerTags (array $tags)
  {
    $this->tags = array_merge ($this->tags, $tags);
  }

}

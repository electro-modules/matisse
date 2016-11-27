<?php
namespace Matisse\Components;

use Matisse\Components\Base\Component;
use Matisse\Parser\DocumentContext;
use Matisse\Properties\Base\MetadataProperties;
use Matisse\Properties\TypeSystem\type;

/**
 * A complex property that is expressed as a subtag.
 *
 * > <p>**Note:** rendering a metadata component **does not** automatically render its children.
 * > <p>Otherwise problems would occur when rendering metadata children, as some of those components may also be
 * metadata.
 * > <p>The content of metadata components, if it is rendered at all, it **must always** be rendered manually on the
 * owner component's `render()`.
 */
class Metadata extends Component
{
  const allowsChildren = true;

  const propertiesClass = MetadataProperties::class;

  /** @var MetadataProperties */
  public $props;
  /**
   * The data type of the property for which this component holds the value.
   *
   * @var number
   */
  public $type;
  /**
   * The parameter's scalar value.
   * Note that data sources are also considered scalar values.
   *
   * @var mixed
   */
  public $value;

  public function __construct (DocumentContext $context, $tagName, $type, array $props = null, array $bindings = null)
  {
    parent::__construct ();
    $this->type = $type;
    $this->setTagName ($tagName);
    $this->setup (null, $context, $props, $bindings);
  }

  /**
   * Converts an array of Metadata trees to actual content that can be rendered.
   *
   * @param self[]    $metadata The metadata to be converted.
   * @param Component $parent   The content will be assigned to this component and will inherit its context.
   * @param bool      $prepend  When true, children are prepended to the existing content, or the the beginning of a
   *                            collection property.
   */
  public static function compile (array $metadata, Component $parent, $prepend = false)
  {
    foreach ($metadata as $item) {
      if (!$item instanceof self) {
        $parent->addChild ($item->cloneWithContext ($parent->context), $prepend);
        continue;
      }
      $tag      = $item->getTagName ();
      $propName = lcfirst ($tag);
      // Insert metadata
      if ($parent->props && !($parent instanceof self) && $parent->props->defines ($propName)) {
        if ($parent->props->getTypeOf ($propName) == type::collection) {
          $comp =
            new self ($parent->context, $tag, $parent->props->getRelatedTypeOf ($propName), $item->props->getAll (),
              $item->bindings);
          if ($prepend)
            array_unshift ($parent->props->$propName, $comp);
          else array_push ($parent->props->$propName, $comp);
        }
        else {
          $comp = new self ($parent->context, $tag, $parent->props->getTypeOf ($propName), $item->props->getAll (),
            $item->bindings);
          $parent->props->set ($propName, $comp);
        }
      }
      // Insert content
      else {
        $comp = $tag === 'Text'
          ? Text::from ($parent->context, $item->value)
          : $parent->context->createComponentFromTag ($tag, $parent, $item->props->getAll (), $item->bindings);
        $parent->addChild ($comp, $prepend);
      }
      // Now, compile the children.
      self::compile ($item->getChildren (), $comp);
    };
  }

  public function export ()
  {
    $a          = parent::export ();
    $a['@tag']  = $this->getTagName ();
    $a['@type'] = $this->type;
    return $a;
  }

  /**
   * Returns the main value of the metadata component.
   * For content-type metadata, this will be the children collection.
   *
   * @return mixed|\Matisse\Components\Base\Component[]
   * @throws \Matisse\Exceptions\ComponentException
   */
  public function getValue ()
  {
    if ($this->type == type::content)
      return $this->getChildren ();
    return $this->value;
  }

  public function import ($a)
  {
    global $usrlz_ctx;

    $parent   = isset($a[MPARENT]) ? $a[MPARENT] : null;
    $props    = isset($a[MPROPS]) ? $a[MPROPS] : null;
    $children = isset($a[MCHILDREN]) ? $a[MCHILDREN] : null;
    $bindings = isset($a[MBINDINGS]) ? $a[MBINDINGS] : null;
    $tag      = $a['@tag'];
    $type     = $a['@type'];

    $this->__construct ($usrlz_ctx, $tag, $type, $props, $bindings);
    $ch =& $this->getChildrenRef (); // We must NOT call setChildren() here!
    $ch = $children; // Assign via reference.
  }

  protected function render ()
  {
    if ($this->type == type::content)
      $this->runChildren ();
  }


}

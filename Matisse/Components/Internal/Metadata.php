<?php
namespace Electro\Plugins\Matisse\Components\Internal;

use Electro\Plugins\Matisse\Components\Base\Component;
use Electro\Plugins\Matisse\Parser\DocumentContext;
use Electro\Plugins\Matisse\Properties\Base\MetadataProperties;
use Electro\Plugins\Matisse\Properties\TypeSystem\type;

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

  public function __construct (DocumentContext $context, $tagName, $type, array $props = null)
  {
    parent::__construct ();
    $this->type = $type;
    $this->setTagName ($tagName);
    $this->setup (null, $context, $props);
  }

  /**
   * Converts an array of Metadata components to actual content that can be rendered.
   *
   * @param self[]    $content
   * @param Component $parent
   */
  public static function compile (array $content, Component $parent)
  {
    foreach ($content as $item) {
      if (!$item instanceof self) {
        $parent->addChild (clone $item);
        continue;
      }
      $tag      = $item->getTagName ();
      $propName = lcfirst ($tag);
      inspect ($propName);
      if ($parent->props && !($parent instanceof self) && $parent->props->defines ($propName)) {
        $comp = new self ($parent->context, $tag, $parent->props->getTypeOf ($propName), $item->props->getAll ());
        $parent->props->set ($propName, $comp);
      }
      else {
        if ($tag === 'Text')
          $comp = Text::from ($parent->context, $item->value);
        else $comp = $parent->context->createComponentFromTag ($tag, $parent, $item->props->getAll (), $item->bindings);
        $parent->addChild ($comp);
      }
      self::compile ($item->getChildren (), $comp);
    };
  }

  /**
   * Returns the main value of the metadata component.
   * For content-type metadata, this will be the children collection.
   *
   * @return mixed|\Electro\Plugins\Matisse\Components\Base\Component[]
   * @throws \Electro\Plugins\Matisse\Exceptions\ComponentException
   */
  public function getValue ()
  {
    if ($this->type == type::content)
      return $this->getChildren ();
    return $this->value;
  }

  protected function render ()
  {
    if ($this->type == type::content)
      $this->runChildren ();
  }


}

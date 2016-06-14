<?php
namespace Selenia\Matisse\Components\Internal;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Parser\DocumentContext;
use Selenia\Matisse\Properties\Base\MetadataProperties;
use Selenia\Matisse\Properties\TypeSystem\type;

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
   * Returns the main value of the metadata component.
   * For content-type metadata, this will be the children collection.
   *
   * @return mixed|\Selenia\Matisse\Components\Base\Component[]
   * @throws \Selenia\Matisse\Exceptions\ComponentException
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

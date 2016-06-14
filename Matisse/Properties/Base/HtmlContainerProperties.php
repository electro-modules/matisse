<?php
namespace Selenia\Matisse\Properties\Base;

use Selenia\Matisse\Components\Internal\Metadata;
use Selenia\Matisse\Properties\TypeSystem\type;

/**
 * Html containers are components that wrap their content with other markup.
 *
 * <p>You may specify the content directly as the component tag's content, or via a specific subtag (`<Content>` by
 * default).
 * <p>The subtag is useful on situations where you need to disambiguate the content (because of tag name clashes, for
 * ex.),
 */
class HtmlContainerProperties extends HtmlComponentProperties
{
  /**
   * @var Metadata|null
   */
  public $content = type::content;

}

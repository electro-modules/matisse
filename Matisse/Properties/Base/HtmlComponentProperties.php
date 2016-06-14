<?php
namespace Selenia\Matisse\Properties\Base;

use Selenia\Matisse\Properties\TypeSystem\type;

/**
 * Properties shared by all {@see HtmlComponent} descendants.
 */
class HtmlComponentProperties extends ComponentProperties
{
  /**
   * @var string The CSS class name to apply to the container HMTL element.
   */
  public $class = '';
  /**
   * @var string If set, the container HMTL element will be assigned this ID, instead of the one from {@see id}
   *      property.
   */
  public $containerId = '';
  /**
   * @var bool
   */
  public $disabled = false;
  /**
   * @var bool
   */
  public $hidden = false;
  /**
   * @var string
   */
  public $htmlAttrs = '';
  /**
   * @var string
   */
  public $id = [type::id, null];

}

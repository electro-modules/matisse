<?php
namespace Matisse\Properties\Base;

use Matisse\Properties\TypeSystem\type;

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
  /**
   * @var string One or more CSS class names to prepend to the component's own class property.
   *             <blockquote><b>Note:</b> this is for exclusive use by theming via presets. Do not use this property as
   *             a tag attribute on a template as it may be overridden by the installed theme (if any).</blockquote>
   */
  public $themeClass = '';

}

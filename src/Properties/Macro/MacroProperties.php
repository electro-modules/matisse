<?php
namespace Matisse\Properties\Macro;

use Matisse\Components\Metadata;
use Matisse\Properties\Base\ComponentProperties;
use Matisse\Properties\TypeSystem\is;
use Matisse\Properties\TypeSystem\type;

class MacroProperties extends ComponentProperties
{
  /**
   * @var string
   */
  public $defaultParam = type::id;
  /**
   * @var string
   */
  public $name = type::id;
  /**
   * @var Metadata[]
   */
  public $param = type::collection;
  /**
   * @var Metadata[]
   */
  public $style = [type::collection, is::of, type::content];
}

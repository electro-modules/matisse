<?php
namespace Electro\Plugins\Matisse\Properties\Macro;

use Electro\Plugins\Matisse\Components\Internal\Metadata;
use Electro\Plugins\Matisse\Properties\Base\ComponentProperties;
use Electro\Plugins\Matisse\Properties\TypeSystem\type;

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
}

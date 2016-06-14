<?php
namespace Selenia\Plugins\Matisse\Properties\Macro;

use Selenia\Plugins\Matisse\Components\Internal\Metadata;
use Selenia\Plugins\Matisse\Properties\Base\ComponentProperties;
use Selenia\Plugins\Matisse\Properties\TypeSystem\type;

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

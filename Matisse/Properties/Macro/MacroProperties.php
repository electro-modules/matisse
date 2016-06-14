<?php
namespace Selenia\Matisse\Properties\Macro;

use Selenia\Matisse\Components\Internal\Metadata;
use Selenia\Matisse\Properties\Base\ComponentProperties;
use Selenia\Matisse\Properties\TypeSystem\type;

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

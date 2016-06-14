<?php
namespace Selenia\Matisse\Components;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Components\Internal\Metadata;
use Selenia\Matisse\Properties\Base\ComponentProperties;
use Selenia\Matisse\Properties\TypeSystem\type;

class ForProperties extends ComponentProperties
{
  /**
   * @var int
   */
  public $count = 0;
  /**
   * @var string Syntax: 'index:var' or 'var' or not set
   */
  public $each = '';
  /**
   * @var Metadata|null
   */
  public $else = type::content;
  /**
   * @var Metadata|null
   */
  public $footer = type::content;
  /**
   * @var Metadata|null
   */
  public $glue = type::content;
  /**
   * @var Metadata|null
   */
  public $header = type::content;
  /**
   * @var mixed
   */
  public $of = type::data;
}

/**
 * Iterates a dataset repeating a block of content for each item.
 */
class For_ extends Component
{
  const allowsChildren = true;

  const propertiesClass = ForProperties::class;

  /** @var ForProperties */
  public $props;

  protected function render ()
  {
    $viewModel = $this->getViewModel ();
    $prop      = $this->props;
    $count     = $prop->get ('count', -1);
    if (exists ($prop->each))
      $this->parseIteratorExp ($prop->each, $idxVar, $itVar);
    else $idxVar = $itVar = null;
    if (!is_null ($for = $prop->of)) {
      $first = true;
      foreach ($for as $i => $v) {
        if ($idxVar)
          $viewModel->$idxVar = $i;
        $viewModel->$itVar = $v;
        if ($first) {
          $first = false;
          $this->runChildren ('header');
        }
        else $this->runChildren ('glue');
        $this->runChildren ();
        if (!--$count) break;
      }
      if ($first)
        $this->runChildren ('noData');
      else $this->runChildren ('footer');
      return;
    }
    if ($count > 0) {
      for ($i = 0; $i < $count; ++$i) {
        $viewModel->$idxVar = $viewModel->$itVar = $i;
        if ($i == 0)
          $this->runChildren ('header');
        else $this->runChildren ('glue');
        $this->runChildren ();
      }
      if ($i) {
        $this->runChildren ('footer');
        return;
      }
    }
    $this->runChildren ('noData');
  }

}

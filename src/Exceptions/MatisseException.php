<?php
namespace Matisse\Exceptions;
use Matisse\Components\Base\Component;

class MatisseException extends \Exception
{
  public $title;

  public function __construct ($message, $title = '', \Exception $previous = null)
  {
    $this->title = $title;
    parent::__construct ($message, 0, $previous);
  }

  protected function inspect (Component $component, $deep = false)
  {
    return $component->inspect ($deep);
  }

}

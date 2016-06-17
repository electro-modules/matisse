<?php
namespace Electro\Plugins\Matisse\Exceptions;

class ReflectionException extends \Exception
{
  public function __construct ($message, ...$args)
  {
    parent::__construct (sprintf($message, ...$args));
  }

}

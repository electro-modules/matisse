<?php
namespace Electro\Plugins\Matisse\Exceptions;

use PhpKit\WebConsole\Lib\Debug;

class ReflectionPropertyException extends ReflectionException
{
  /**
   * ReflectionPropertyException constructor.
   * @param string $className
   * @param string $propertyName
   * @param string $message
   * @param array  ...$args
   */
  public function __construct ($className, $propertyName, $message, ...$args)
  {
    array_push ($args, Debug::formatClassName ($className), $propertyName);
    parent::__construct (sprintf ("$message.<p>Property %s-&gt;<kbd>%s</kbd>.", ...$args));
  }

}

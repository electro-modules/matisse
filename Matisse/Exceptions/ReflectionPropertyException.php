<?php
namespace Selenia\Matisse\Exceptions;

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
    array_push ($args, formatClassName ($className), $propertyName);
    parent::__construct (sprintf ("$message.<p>Property %s-&gt;<kbd>%s</kbd>.", ...$args));
  }

}

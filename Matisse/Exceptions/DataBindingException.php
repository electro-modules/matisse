<?php
namespace Electro\Plugins\Matisse\Exceptions;

class DataBindingException extends MatisseException
{
  public function __construct ($msg)
  {
    parent::__construct ($msg, 'Databinding error');
  }

}

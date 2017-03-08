<?php

namespace Matisse\Components;

use Electro\Exceptions\FlashType;
use Electro\Interfaces\SessionInterface;
use Matisse\Components\Base\HtmlComponent;
use Matisse\Properties\Base\HtmlComponentProperties;

/**
 * Displays the flash message currently stored on the session, if any.
 */
class FlashMessage extends HtmlComponent
{
  const propertiesClass = HtmlComponentProperties::class;
  /**
   * @var SessionInterface
   */
  private $session;

  public function __construct (SessionInterface $session)
  {
    parent::__construct ();
    $this->session = $session;
  }

  protected function isVisible ()
  {
    return exists ($this->session->getFlashMessage ()) && parent::isVisible ();
  }

  protected function render ()
  {
    if (!$this->props->class)
      $this->addClass ('alert');
    $msg   = $this->session->getFlashMessage ();
    $class = FlashType::getLabel ($msg['type']);
    $this->addClass ($class);
    $this->beginContent ();
    echo $msg['message'];
  }

}

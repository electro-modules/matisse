<?php

namespace Matisse\Components;

use Electro\Exceptions\FlashType;
use Electro\Interfaces\SessionInterface;
use Matisse\Components\Base\Component;

/**
 * Displays the flash message currently stored on the session, if any.
 */
class FlashMessage extends Component
{

  /**
   * @var SessionInterface
   */
  private $session;

  public function __construct (SessionInterface $session)
  {
    parent::__construct ();
    $this->session = $session;
  }

  protected function render ()
  {
    $msg = $this->session->getFlashMessage ();
    if ($msg) {
      $class = FlashType::getLabel ($msg['type']);
      echo "<div class='alert $class'>{$msg['message']}</div>";
    }
  }

}

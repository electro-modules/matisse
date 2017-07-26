<?php

namespace Matisse\Components;

use Electro\Interfaces\SessionInterface;
use Matisse\Components\Base\HtmlComponent;
use Matisse\Properties\Base\HtmlComponentProperties;

class FlashMessageProperties extends HtmlComponentProperties
{
	public $errorClass = 'alert alert-danger';
	public $infoClass = 'alert alert-info';
	public $successClass = 'alert alert-success';
	public $warningClass = 'alert alert-warning';
}

/**
 * Displays the flash message currently stored on the session, if any.
 */
class FlashMessage extends HtmlComponent
{
	const TYPE_PROP_NAMES = [
		'', 'errorClass', 'warningClass', 'infoClass', 'successClass',
	];
	const propertiesClass = FlashMessageProperties::class;
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

	protected function postRender ()
	{
		parent::postRender();
	}

	protected function preRender ()
	{
		$msg       = $this->session->getFlashMessage ();
		$classProp = self::TYPE_PROP_NAMES[$msg['type']];
		if ($classProp)
			$this->addClass ($this->props->$classProp);

		parent::preRender();
	}

	protected function render ()
	{
		$this->beginContent();
		$msg       = $this->session->getFlashMessage ();
		echo $msg['message'];
	}

}

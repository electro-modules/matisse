<?php
namespace Matisse\Components;

use Electro\Interfaces\Http\Shared\CurrentRequestInterface;
use Electro\Interfaces\Navigation\NavigationInterface;
use Electro\Kernel\Config\KernelSettings;
use Matisse\Components\Base\Component;
use Matisse\Properties\Base\ComponentProperties;
use Matisse\Properties\TypeSystem\type;

class FetchableProperties extends ComponentProperties
{
  /**
   * A collection of Channel components that have been moved from their original location on the template.
   * They hold the content that is rendered on Fetch requests.
   *
   * @var Metadata
   */
  public $channels = type::content;
}

/**
 * Wraps the whole content of a page that is Fetch-capable, i.e. fragments of it can be loaded via Fetch (aka AJAX).
 *
 * <p>If the request is a normal request the component's children will be rendered as usual.
 *
 * <p>If the request is a Fetch request, only {@see Channel} components will run, all other children will be ignored.
 */
class Fetchable extends Component
{
  const allowsChildren  = true;
  const propertiesClass = FetchableProperties::class;

  /** @var FetchableProperties */
  public $props;
  /** @var KernelSettings */
  private $kernelSettings;
  /** @var NavigationInterface */
  private $navigation;
  /** @var CurrentRequestInterface */
  private $request;

  // public function __construct (CurrentRequestInterface $request, NavigationInterface $navigation,
  //                              KernelSettings $kernelSettings)
  public function __construct (CurrentRequestInterface $request)
  {
    parent::__construct ();
    $this->request        = $request;
    // $this->navigation     = $navigation;
    // $this->kernelSettings = $kernelSettings;
  }

  protected function init ()
  {
    parent::init ();
    $this->context->fetchableRoot = $this;
  }


  protected function render ()
  {
    $isFetch = $this->request->getAttribute ('isFetch');
/*    if ($isFetch)
      echo sprintf ('<title>%s - %s</title>

', $this->navigation->currentLink ()->title (), $this->kernelSettings->appName);*/
    $this->runChildren ('channels');
    if (!$isFetch)
      $this->runChildren ();
  }

}

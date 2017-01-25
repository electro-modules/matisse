<?php
namespace Matisse\Traits\Component;

use Electro\Interop\ViewModel;
use Matisse\Components\DocumentFragment;

trait ViewModelTrait
{
  /**
   * @var ViewModel|null This is only set if the view is not a Matisse template.
   */
  private $shadowViewModel = null;

  /**
   * Returns the component's view model.
   *
   * >#####Important
   * >On a composite component, the view model data is set on the shadow DOM's view model,
   * **NOT** on the component's own view model!
   * <p>If the view is not a Matisse template (and therefore there is no shadow DOM), an alternate view model is used
   * (see {@see $shadowViewModel}).
   * ><p>This method overrides {@see DataBindingTrait} to implement that behavior.
   *
   * @return \Electro\Interop\ViewModel
   */
  function getViewModel ()
  {
    /** @var DocumentFragment $shadowDOM */
    $shadowDOM = $this->getShadowDOM ();
    return $shadowDOM
      ? $shadowDOM->getDataBinder ()->getViewModel ()
      : ($this->shadowViewModel ?: $this->shadowViewModel = new ViewModel);
  }

  /**
   * Extension hook.
   *
   * @override
   */
  protected function afterPreRun ()
  {
    parent::afterPreRun ();

    $vm = $this->getViewModel ();
    $this->baseViewModel ($vm);
    $this->viewModel ($vm);

    /** @var \Matisse\Components\DocumentFragment $shadowDOM */
    $shadowDOM = $this->getShadowDOM ();
    if ($shadowDOM)
      $shadowDOM->getDataBinder ()->setProps ($this->props ?: $this->getDataBinder ()->getProps ());
  }

  /**
   * Override to set data on the component's view model.
   *
   * ><p>You should not need to call `parent::viewModel` if you override this method, as this is meant to be overridden
   * only once, on your page controller.
   *
   * @param ViewModel $viewModel The view model where data can be stored for later access by the view renderer.
   */
  protected function viewModel (ViewModel $viewModel)
  {
    //override
  }

  /**
   * Override to set data on the component's view model that will be set for component subclasses.
   *
   * ><p>Don't forget to call `parent::baseViewModel` if you override this method.
   *
   * @param \Electro\Interop\ViewModel $viewModel The view model where data can be stored for later access by the view renderer.
   */
  protected function baseViewModel (ViewModel $viewModel)
  {
    //override
  }

}

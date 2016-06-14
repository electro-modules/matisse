<?php
namespace Selenia\Matisse\Traits\Component;

use Selenia\Matisse\Components\Internal\DocumentFragment;
use Selenia\ViewEngine\Lib\ViewModel;

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
   * <p>If the view is not a Matisse template (amd therefore there is no shadow DOM), an alternate view model is used
   * (see {@see $shadowViewModel}).
   * ><p>This method overrides {@see DataBindingTrait} to implement that behavior.
   *
   * @return ViewModel
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

    $this->viewModel ($this->getViewModel ());

    /** @var DocumentFragment $shadowDOM */
    $shadowDOM = $this->getShadowDOM ();
    if ($shadowDOM)
      $shadowDOM->getDataBinder ()->setProps ($this->props ?: $this->getDataBinder ()->getProps ());
  }

  /**
   * Override to set data on the component's view model.
   *
   * @param ViewModel $viewModel The view model where data can be stored for later access by the view renderer.
   */
  protected function viewModel (ViewModel $viewModel)
  {
    //override
  }

}

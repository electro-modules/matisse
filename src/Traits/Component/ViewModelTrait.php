<?php

namespace Matisse\Traits\Component;

use Electro\Interfaces\Views\ViewModelInterface;
use Matisse\Components\Base\Component;
use Matisse\Components\DocumentFragment;
use Matisse\Interfaces\DataBinderInterface;

trait ViewModelTrait
{
  /**
   * @var ViewModelInterface|null This is only set if the view is not a Matisse template.
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
   * @return ViewModelInterface
   */
  function getViewModel ()
  {
    if (isset($this->shadowViewModel))
      return $this->shadowViewModel;
    /** @var DocumentFragment $shadowDOM */
    $shadowDOM = $this->getShadowDOM ();
    return $shadowDOM
      ? $shadowDOM->getDataBinder ()->getViewModel ()
      : null;
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
    if ($vm) {
      $this->baseViewModel ($vm);
      $this->viewModel ($vm);
    }

    /** @var \Matisse\Components\DocumentFragment $shadowDOM */
    $shadowDOM = $this->getShadowDOM ();
    if ($shadowDOM)
      $shadowDOM->getDataBinder ()->setProps ($this->props ?: $this->getDataBinder ()->getProps ());
  }

  /**
   * Override to set data on the component's view model that will be set for component subclasses.
   *
   * ><p>Don't forget to call `parent::baseViewModel` if you override this method.
   *
   * @param ViewModelInterface $viewModel The view model where data can be stored for later access by the view renderer.
   */
  protected function baseViewModel (ViewModelInterface $viewModel)
  {
    /** @var DataBinderInterface $binder */
    $binder = $this->getDataBinder ();
    $props  = $binder->getProps ();
    if ($props)
      $viewModel['props'] = $props->getAll ();
  }

  protected function setViewModel (ViewModelInterface $viewModel)
  {
    // For debugging:
    // $viewModel['_class'] = typeOf ($viewModel);
    // $viewModel['_keys'] = array_keys ($viewModel->getArrayCopy ());

    /** @var Component $dom */
    $dom = $this->provideShadowDOM ();
    if ($dom)
      $dom->getDataBinder ()->setViewModel ($viewModel);
    else $this->shadowViewModel = $viewModel;
  }

  /**
   * Override to set data on the component's view model.
   *
   * ><p>You should not need to call `parent::viewModel` if you override this method, as this is meant to be overridden
   * only once, on your page controller.
   *
   * @param ViewModelInterface $viewModel The view model where data can be stored for later access by the view renderer.
   */
  protected function viewModel (ViewModelInterface $viewModel)
  {
    //override
  }

}

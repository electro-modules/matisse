<?php

namespace Matisse\Traits\Component;

use Electro\Interfaces\Views\ViewModelInterface;
use Matisse\Components\Base\Component;
use Matisse\Components\DocumentFragment;
use Matisse\Properties\Base\AbstractProperties;

/**
 * @property AbstractProperties $props
 */
trait ViewModelTrait
{
  /**
   * @var ViewModelInterface|null This is only set if the view is not a Matisse template.
   */
  private $shadowViewModel = null;

  /**
   * Returns the component's view model.
   *
   * #####Important
   * On a composite component, the view model data is set on the shadow DOM's view model, **NOT** on the component's
   * own view model, so that bindings on the component's properties (which may hold DOMs themselves) are computed on the
   * current binding context, not on the shadow DOM's context, which is a subdocument with its own isolated context.
   *
   * <p>If the view is not a Matisse template (and therefore there is no shadow DOM), an alternate view model is used
   * (see {@see $shadowViewModel}).
   *
   * ><p>This method overrides {@see DataBindingTrait}.
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
      $vm->init ();
    }
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
    $props = $this->props ? $this->props->getAll () : null;
    if ($props)
      $viewModel['props'] = $props;
  }

  protected function setViewModel (ViewModelInterface $viewModel)
  {
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

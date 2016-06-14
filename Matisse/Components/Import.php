<?php
namespace Selenia\Matisse\Components;

use Interop\Container\Exception\NotFoundException;
use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Properties\Base\ComponentProperties;

class UseProperties extends ComponentProperties
{
  /**
   * @var string The key under which to store the imported service class/interface.
   */
  public $as = '';
  /**
   * @var string This is an alias of {@see services}
   */
  public $service = '';
  /**
   * @var string A space-delimited list of service names.
   */
  public $services = '';
}

/**
 * A component that injects services from the Dependency Injection container directly into a view's view-model.
 *
 * <p>The `<Import>` component is meant to be used mainly on:
 *
 *  1. Template partials that are shared between pages on your app.
 *     <p>By using components of this type on a template, you are saved from having to create a PHP class for each
 *     template just for setting common/shared data on their view-models.
 *
 *  2. Page templates, for reducing the amount of services that would have to be injected into the
 *     page controller's constructor (when those services are only used on the view), therefore reserving that
 *     constructor for the injection of business-related services only.
 *
 * <p>The benefits of using this approach include the reduction of the amount of boilerplate code that has be
 * written, and the reduction of the coupling between the page's businness logic and the view-related logic, among
 * others.
 *
 * ##### Syntax
 * ```
 * <Import service=Fully\Qualified\ClassName as=yourService/>
 * or
 * <Import service=serviceAlias/>
 * or
 * <Import services="serviceAlias1 serviceAlias2 ..."/>
 * ```
 * On the template, after the `Use` declaration, you can use {yourService.property.or.getterMethod} or
 * {serviceAlias.property.or.getterMethod} data binding expressions to access data from the injected services.
 *
 * ##### Using short names (aliases) for services
 *
 * Instead of typing the fully qualified service class name (which, by the way, is not refactor-friendly), you may
 * instead refer to a previously defined service alias.
 * <p>Selenia already provides some predefined aliases for the most common services.
 * <p>You may also set additional service alias mappings using {@see InjectorInterface::set()} or
 * {@see InjectorInterface::share()}.
 */
class Import extends Component
{
  const propertiesClass = UseProperties::class;

  /** @var UseProperties */
  public $props;

  protected function render ()
  {
    try {
      $prop     = $this->props;
      $services = $prop->service ?: $prop->services;
      if ($services) {
        $vm       = $this->context->getDataBinder ()->getViewModel ();
        $injector = $this->context->injector;
        $aliases = preg_split ('/\s+/', $services, -1, PREG_SPLIT_NO_EMPTY);

        if (exists ($as = $prop->as)) {
          if (count ($aliases) > 1)
            throw new ComponentException ($this,
              "When using the <kbd>as</kbd> property, you can only specify one value for the <kbd>service</kbd> property");
          $service = $injector->make ($services);
          $vm->$as = $service;
        }
        else {
          foreach ($aliases as $alias) {
            $service    = $injector->get ($alias);
            $vm->$alias = $service;
          }
        }
      }
    }
    catch (NotFoundException $e) {
      throw new ComponentException ($this, $e->getMessage ());
    }
  }

}

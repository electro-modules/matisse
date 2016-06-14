<?php
namespace Selenia\Matisse\Traits\Component;

use PhpKit\WebConsole\ErrorConsole\ErrorConsole;
use PhpKit\WebConsole\Lib\Debug;
use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Exceptions\DataBindingException;
use Selenia\Matisse\Interfaces\DataBinderInterface;
use Selenia\Matisse\Parser\Expression;
use Selenia\Matisse\Properties\Base\ComponentProperties;
use Selenia\ViewEngine\Lib\ViewModel;

/**
 * Provides an API for handling data binding on a component's properties.
 *
 * It's applicable to the Component class.
 *
 * @property ComponentProperties $props The component's attributes.
 */
trait DataBindingTrait
{
  /**
   * A map of attribute names and corresponding databinding expressions.
   * Equals NULL if no bindings are defined.
   *
   * > <p>It has `public` visibility so that it can be inspected externally.
   *
   * @var Expression[]|null
   */
  protected $bindings = null;

  /**
   * Registers a data binding.
   *
   * @param string     $prop    The name of the bound property.
   * @param Expression $bindExp The binding expression.
   */
  function addBinding ($prop, Expression $bindExp)
  {
    if (!isset($this->bindings))
      $this->bindings = [];
    $this->bindings[$prop] = $bindExp;
  }

  /**
   * Binds a property to a databinding expression.
   *
   * @param string              $prop       A property name.
   * @param Expression[]|string $expression If a string is given, it will be converted to an {@see Expression} instance.
   */
  function bind ($prop, $expression)
  {
    $this->bindings[$prop] = $expression instanceof Expression ? $expression : new Expression($expression);
  }

  /**
   * Gets the databinding expression to which the given property is bound, if any.
   *
   * @param string $prop A property name.
   * @return Expression[]|null null if the property is not bound.
   */
  function getBinding ($prop)
  {
    return get ($this->bindings, $prop);
  }

  /**
   * Gets the component's property bindings map.
   *
   * @return Expression[]
   */
  public function getBindings ()
  {
    return $this->bindings;
  }

  /**
   * Sets the component's property bindings map.
   *
   * @param Expression[]|null $bindings
   */
  public function setBindings (array $bindings = null)
  {
    $this->bindings = $bindings;
  }

  /**
   * Returns the current value of an attribute, performing databinding if necessary.
   *
   * <p>This is only required on situation where you need a property's value before databinging has occured.
   *
   * @param string $name
   * @return mixed
   * @throws DataBindingException
   */
  function getComputedPropValue ($name)
  {
    if (isset($this->bindings[$name]))
      return $this->evalBinding ($this->bindings[$name]);

    return $this->props->get ($name);
  }

  /**
   * Gets the component's data binder.
   *
   * @return DataBinderInterface
   */
  function getDataBinder ()
  {
    return $this->context->getDataBinder ();
  }

  /**
   * Returns the component's view model (its own or an inherited one).
   *
   * >#####Important
   * >On a composite component, the view model data is set on the shadow DOM's view model,
   * **NOT** on the component's own view model!
   * ><p>This method is overridden on {@see ViewModelTrait} to implement that behavior.
   *
   * @return ViewModel
   */
  function getViewModel ()
  {
    return $this->context->getDataBinder ()->getViewModel ();
  }

  /**
   * Checks of a property is bound to a databinding expression.
   *
   * @param string $prop A property name.
   * @return bool
   */
  function isBound ($prop)
  {
    return !missing ($this->bindings, $prop);
  }

  /**
   * Indicates if either a constant value or a databinding expression were specified for the given property.
   *
   * @param string $fieldName
   * @return boolean
   */
  function isPropertySet ($fieldName)
  {
    return isset($this->props->$fieldName) || $this->isBound ($fieldName);
  }

  /**
   * Removes the binding from a given property, if one exists.
   *
   * @param string $prop A property name.
   */
  function removeBinding ($prop)
  {
    if (isset($this->bindings)) {
      unset($this->bindings[$prop]);
      if (empty($this->bindings))
        $this->bindings = null;
    }
  }

  /**
   * Evaluates all of the component's bindings.
   *
   * @throws ComponentException
   */
  protected function databind ()
  {
    if (isset($this->bindings))
      foreach ($this->bindings as $attrName => $bindExp) {
        $value = $this->evalBinding ($bindExp);
        if (is_object ($value))
          $this->props->$attrName = $value;
        else $this->props->set ($attrName, $value);
      }
  }

  /**
   * Evaluates the given binding expression on the component's context.
   *
   * <p>This method is an **extension hook** that allows a subclass to modify the evaluation result.
   * > <p>**Ex.** see the {@see Text} component.
   *
   * @param Expression $bindExp
   * @return mixed
   * @throws ComponentException
   * @throws DataBindingException
   */
  protected function evalBinding (Expression $bindExp)
  {
    $binder = $this->getDataBinder ();
    if (!$binder) {
      _log ()->warning ("No binder is set for evaluating an expression on a " . $this->getTagName () . " component");
      return null;
    }
    try {
      /** @var Component $this */
      return $bindExp->evaluate ($binder);
    }
    catch (\Exception $e) {
      self::evalError ($e, $bindExp);
    }
    catch (\Error $e) {
      self::evalError ($e, $bindExp);
    }
  }

  /**
   * Parses a component iterator property. Iterators are used by the `For` component, for instance.
   *
   * @param string $exp
   * @param string $idxVar
   * @param string $itVar
   * @throws ComponentException
   */
  protected function parseIteratorExp ($exp, & $idxVar, & $itVar)
  {
    if (!preg_match ('/^(?:(\w+):)?(\w+)$/', $exp, $m))
      throw new ComponentException($this,
        "Invalid value for attribute <kbd>as</kbd>.<p>Expected syntax: <kbd>'var'</kbd> or <kbd>'index:var'</kbd>");
    list (, $idxVar, $itVar) = $m;
  }

  /**
   * @param \Error|\Exception $e
   * @param Expression        $exp
   * @throws ComponentException
   */
  private function evalError ($e, Expression $exp)
  {
    throw new ComponentException ($this,
      Debug::grid ([
        'Expression' => Debug::RAW_TEXT . "<kbd>$exp</kbd>",
        'Compiled'   => sprintf ('%s<code>%s</code>', Debug::RAW_TEXT, \PhpCode::highlight ("$exp->translated")),
        'Error'      => sprintf ('%s%s %s', Debug::RAW_TEXT, typeInfoOf ($e), $e->getMessage ()),
        'At'         => sprintf ('%s%s, line <b>%s</b>', Debug::RAW_TEXT,
          ErrorConsole::errorLink ($e->getFile (), $e->getLine ()), $e->getLine ()),
      ], 'Error while evaluating data-binding expression')
    );
  }

}

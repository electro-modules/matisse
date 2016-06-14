<?php
namespace Selenia\Matisse\Traits\Component;

use Selenia\Matisse\Components\Base\Component;
use Selenia\Matisse\Components\Internal\DocumentFragment;
use Selenia\Matisse\Components\Internal\Metadata;
use Selenia\Matisse\Debug\ComponentInspector;
use Selenia\Matisse\Exceptions\ComponentException;
use Selenia\Matisse\Properties\Base\ComponentProperties;

/**
 * Provides an API for manipulating DOM nodes on a tree of components.
 *
 * It's applicable to the Component class.
 *
 * @property DocumentFragment    $root
 * @property ComponentProperties $props
 */
trait DOMNodeTrait
{
  /**
   * Points to the parent component in the page hierarchy.
   * It is set to NULL if the component is the top one (a Page instance) or if it's standalone.
   *
   * @var Component|null
   */
  public $parent = null;
  /**
   * An array of child components that are either defined on the source code or
   * generated dinamically.
   *
   * <p>This can never be `null`.
   * <p>**READONLY** - Never set this directly.
   *
   * @var Component[]
   */
  private $children = [];

  /**
   * @param Component[] $components
   * @param Component   $parent
   * @return Component[]|null
   */
  public static function cloneComponents (array $components = null, Component $parent = null)
  {
    if (isset($components)) {
      $result = [];
      foreach ($components as $component) {
        /** @var Component $cloned */
        $cloned = clone $component;
        if (isset($parent))
          $cloned->attachTo ($parent);
        else $cloned->detach ();
        $result[] = $cloned;
      }

      return $result;
    }

    return null;
  }

  /**
   * @param Component[]|null $components
   */
  static public function detachAll (array $components)
  {
    foreach ($components as $child)
      $child->detach ();
  }

  /**
   * @param Component[] $components
   */
  static public function removeAll (array $components)
  {
    foreach ($components as $c)
      $c->remove ();
  }

  public function __clone ()
  {
    if (isset($this->props)) {
      $this->props = clone $this->props;
      $this->props->setComponent ($this);
    }
    if ($this->children)
      $this->children = self::cloneComponents ($this->children, $this);
  }

  /**
   * @param Component $child
   */
  public function addChild (Component $child)
  {
    if ($child) {
      $this->children[] = $child;
      $this->attach ($child);
    }
  }

  /**
   * @param Component[]|null $children
   */
  public function addChildren (array $children = null)
  {
    if ($children) {
      array_mergeInto ($this->children, $children);
      $this->attach ($children);
    }
  }

  /**
   * Can this component have children?
   *
   * @return bool
   */
  public function allowsChildren ()
  {
    return static::allowsChildren;
  }

  /**
   * @param Component|Component[] $childOrChildren
   */
  public function attach ($childOrChildren = null)
  {
    if (!empty($childOrChildren)) {
      if (is_array ($childOrChildren))
        foreach ($childOrChildren as $child)
          /** @var Component $child */
          $child->attachTo ($this);
      else $childOrChildren->attachTo ($this);
    }
  }

  /**
   * @param Component $parent
   */
  public function attachTo (Component $parent)
  {
    $this->parent  = $parent;
    if (!$this->context)
      $this->context = $parent->context;
  }

  /**
   * Detaches the component from its parent.
   *
   * <p>This does **not** remove the component from the children set of the parent.
   */
  public function detach ()
  {
    $this->parent = null;
  }

  /**
   * @param string|null $attrName [optional] An attribute name. If none, returns all the component's children.
   * @return Component[] Never null.
   * @throws ComponentException If the specified attribute is not a parameter.
   */
  public function getChildren ($attrName = null)
  {
    if (is_null ($attrName))
      return $this->children;

    if (isset($this->props->$attrName)) {
      $p = $this->props->$attrName;
      if ($p instanceof Metadata)
        return $p->children;
      throw new ComponentException($this,
        "Can' get children of attribute <b>$attrName</b>, which has a value of type <b>" . gettype ($p) . '</b>.');
    }
    return [];
  }

  /**
   * Replaces the current children with the supplied ones.
   *
   * > <p>**Warning:** the previou children (if any) will be detached and removed from their respective parents.
   *
   * @param array $components
   */
  public function setChildren (array $components = [])
  {
    self::removeAll ($components);
    $this->children = $components;
    $this->attach ($components);
  }

  /**
   * Returns a reference to the children collection.
   * > <p>**Note:** use this only for in-place manipulation of the children list, without causing redundant
   * attachments and detachments (which would occur, for instance, if you called `setChildren()`).
   *
   * > <p>**Avoid this** unless it's really neccessary.
   *
   * @return Component[] A reference. Never null.
   */
  public function & getChildrenRef ()
  {
    return $this->children;
  }

  /**
   * @param string|null $attrName
   * @return Component[]
   * @throws ComponentException
   */
  public function getClonedChildren ($attrName = null)
  {
    return self::cloneComponents ($this->getChildren ($attrName));
  }

  /**
   * Returns the first child component, if any.
   *
   * @return null|Component
   */
  public function getFirstChild ()
  {
    return $this->children ? $this->children[0] : null;
  }

  /**
   * Returns the ordinal index of this component on the parent's child list.
   *
   * @return int|boolean
   * @throws ComponentException
   */
  public function getIndex ()
  {
    if (!isset($this->parent))
      throw new ComponentException($this, "The component is not attached to a parent.");
    if (!$this->parent->children)
      throw new ComponentException($this, "The parent component has no children.");

    return array_search ($this, $this->parent->children, true);
  }

  /**
   * @return bool True if the component has any children at all.
   */
  function hasChildren ()
  {
    return !empty($this->children);
  }

  /**
   * Returns the ordinal index of the specified child on this component's child list.
   *
   * @param Component $child
   * @return bool|int
   */
  public function indexOf (Component $child)
  {
    return array_search ($child, $this->children, true);
  }

  /**
   * Removes the component from its parent's children list.
   *
   * @throws ComponentException
   */
  public function remove ()
  {
    if (isset($this->parent))
      $this->parent->removeChild ($this);
  }

  public function removeChild (Component $child)
  {
    $p = $this->indexOf ($child);
    if ($p === false)
      throw new ComponentException($child,
        "The component is not a child of the specified parent, so it cannot be removed.");
    array_splice ($this->children, $p, 1);
    $child->detach ();
  }

  /**
   * Removes, detaches and returns the component's children.
   */
  public function removeChildren ()
  {
    $children       = $this->children;
    $this->children = [];
    self::detachAll ($children);
    return $children;
  }

  /**
   * Replaces the component by the specified componentes in the parent's child list.
   * The component itself is discarded from the components tree.
   *
   * @param array $components
   * @throws ComponentException
   */
  public function replaceBy (array $components = null)
  {
    $p = $this->getIndex ();
    if ($p !== false) {
      array_splice ($this->parent->children, $p, 1, $components);
      $this->parent->attach ($components);
    }
    else {
      $t = ComponentInspector::inspectSet ($this->parent->children);
      throw new ComponentException($this,
        "The component was not found on the parent's children.<h3>The children are:</h3><fieldset>$t</fieldset>");
    }
  }

  /**
   * Replaces the component by its contents in the parent's child list.
   * The component itself is therefore discarded from the components tree.
   */
  public function replaceByContents ()
  {
    $this->replaceBy ($this->children);
  }

}

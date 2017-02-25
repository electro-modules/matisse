<?php

namespace Matisse\Components;

use Electro\Interfaces\Http\Shared\CurrentRequestInterface;
use Matisse\Components\Base\Component;
use Matisse\Exceptions\ComponentException;
use Matisse\Properties\Base\ComponentProperties;
use Matisse\Properties\TypeSystem\type;

class ContentProperties extends ComponentProperties
{
  /**
   * The block name. If you set it via this property, the new content will be appended to the saved content (if any).
   *
   * @var string
   */
  public $appendTo = type::id;
  /**
   * Modifies the saved content only if none is set yet.
   *
   * @var bool
   */
  public $byDefault = false;
  /**
   * Makes the block auto-renderable for FETCH requests.
   *
   * <p>When true, the content is automatically output to a `<section id=blockName>` tag when
   * <kbd>request&#64;isFetch</kbd> is true.
   *
   * @var bool
   */
  public $fetchable = false;
  /**
   * The block name. If you set it via this property, the new content will overwrite the saved content (if any).
   *
   * @var string
   */
  public $of = type::id;
  /**
   * @var bool If true, the content is rendered immediately when defined and stored as a string for later retrieval.
   */
  public $preRender = false;
  /**
   * The block name. If you set it via this property, the new content will be prepended to the saved content (if any).
   *
   * @var string
   */
  public $prependTo = type::id;
  /**
   * Alternative to setting the content via the tag's content; useful for short strings.
   * If set, the tag's content is ignored.
   *
   * @var string
   */
  public $value = type::string;
}

/**
 * The Content component allows you to save HTML and/or components on named memory containers, and yield them later at
 * specific locations on your document.
 *
 * ###### Ex:
 * ```HTML
 *   <!-- First define a block named 'header' -->
 *   <Content of="header">
 *     <h1>A Header</h1>
 *   </Content>
 *
 *   <!-- Now output the block by name -->
 *   {#header|*}
 * ```
 * <p>You can also use the `{#name}` syntax to putput a block, but note that it escapes its output, which is, usually,
 * not what you intend, if you are sure the content being output is safe HTML.
 *
 * <p>Content blocks are also used for Fetch (aka. AJAX) requests, to render only the parts of a page that changed.
 * <br>For that purpose, enable the `fetchable` property and it will work automatically.
 */
class Content extends Component
{
  const allowsChildren = true;

  const propertiesClass = ContentProperties::class;

  /** @var ContentProperties */
  public $props;
  /** @var CurrentRequestInterface */
  private $request;

  public function __construct (CurrentRequestInterface $request)
  {
    parent::__construct ();
    $this->request = $request;
  }

  /**
   * Adds (or replaces) the content of the `value` property (or the component's content) to a named block on the page.
   */
  protected function render ()
  {
    $prop          = $this->props;
    $content       = exists ($prop->value) ? $prop->value : $this->getChildren ();
    $blocksService = $this->context->getBlocksService ();
    $isFetch       = $this->request->getAttribute ('isFetch');

    // Non-fetchable blocks are not processed on Fetch requests.
    if ($isFetch && !$prop->fetchable)
      return;

    if ($prop->preRender && is_array ($content))
      $content = $this->attachSetAndGetContent ($content);

    if (exists ($name = $prop->of)) {
      if (!preg_match ('/^\w+$/', $name))
        throw new ComponentException($this, "Invalid block name: <kbd>$name</kbd>");
      if ($prop->byDefault && $blocksService->hasBlock ($name))
        return;
      $blocksService->getBlock ($name)->set ($content);
    }
    elseif (exists ($name = $prop->appendTo)) {
      if ($prop->byDefault && $blocksService->hasBlock ($name))
        return;
      $blocksService->getBlock ($name)->append ($content);
    }
    elseif (exists ($name = $prop->prependTo)) {
      if ($prop->byDefault && $blocksService->hasBlock ($name))
        return;
      $blocksService->getBlock ($name)->prepend ($content);
    }
    else throw new ComponentException($this,
      "One of these properties must be set:<p><kbd>of | appendTo | prependTo</kbd>");

    // If on Fetch mode, render the block immediately as a `<section>` element.
    if ($isFetch && $prop->fetchable)
      echo "<section id='$name'>" . $blocksService->getBlock ($name)->render () . "</section>";
  }

}


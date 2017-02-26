<?php

namespace Matisse\Components;

use Electro\Interfaces\Http\Shared\CurrentRequestInterface;
use Matisse\Components\Base\Component;
use Matisse\Exceptions\ComponentException;
use Matisse\Parser\Expression;
use Matisse\Properties\Base\ComponentProperties;
use Matisse\Properties\TypeSystem\type;

class ChannelProperties extends ComponentProperties
{
  /**
   * Additional HTML attributes for the generated tag that encloses the page fragment rendered by the channel.
   *
   * @var string
   */
  public $attributes = '';
  /**
   * A unique identifier for this channel.
   *
   * @var string
   */
  public $name = type::id;
  /**
   * The name of the generated HTML tag that will enclose the page fragment rendered by the channel.
   *
   * @var string
   */
  public $tag = 'section';
}

/**
 * The Channel component allows you to delimit a fragment of an HTML document that can be rendered independently via
 * Fetch (aka AJAX) requests.
 *
 * ###### Example
 * ```HTML
 * <div class="xx">
 *   <div>some content</div>
 *
 *   <Channel name="sidebar">
 *     <h1>Main Menu</h1>
 *     <ul>
 *       <li>Home</li>
 *     </ul>
 *   </Channel>
 *
 *   <div>other content</div>
 * </div>
 * ```
 * <p>If you include at least one `Channel` component on a page, a Javascript API will also be included to allow
 * reloading parts of the page via Fetch.
 */
class Channel extends Component
{
  const allowsChildren = true;

  const propertiesClass = ChannelProperties::class;

  /** @var ChannelProperties */
  public $props;
  /** @var string */
  private $channelId;
  /** @var CurrentRequestInterface */
  private $request;

  public function __construct (CurrentRequestInterface $request)
  {
    parent::__construct ();
    $this->request = $request;
  }

  function onParsingComplete ()
  {
    $prop = $this->props;
    $name = $prop->name;
    if (!preg_match ('/^\w+$/', $name))
      throw new ComponentException($this, "Invalid block name: <kbd>$name</kbd>");

    $this->channelId = 'channel' . ucfirst ($name);
    $exp             = sprintf ("{'<%s id=%s%s>'+#%s+'</%s>'|*}", $prop->tag, $name,
      $prop->attributes ? " $prop->attributes" : '', $this->channelId, $prop->tag);
    $root            = $this->context->rootComponent;
    $text            = Text::from ($this->context);
    $text->addBinding ('value', new Expression($exp));
    $this->replaceBy ([$text]);

    $root->addChild ($this);
    $this->setContext ($root->context);
  }


  /**
   * Adds (or replaces) the content of the `value` property (or the component's content) to a named block on the page.
   */
  protected function render ()
  {
    $prop          = $this->props;
    $content       = $this->getChildren ();
    $blocksService = $this->context->getBlocksService ();
    $isFetch       = $this->request->getAttribute ('isFetch');

    // Save the channel's content into a content block.
    $blocksService->getBlock ($this->channelId)->set ($content);

    // If on Fetch mode, render the block immediately as a `<section>` element.
    if ($isFetch) {
      echo "<section id='$prop->name'>
";
      $this->runChildren ();
      echo "
</section>

";
    }
  }

}


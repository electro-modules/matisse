<?php
namespace Selenia\Matisse\Components\Internal;

use Selenia\Matisse\Components\Base\Component;

/**
 * The root node of a component tree, which may be an entire document or just a part of one.
 */
class DocumentFragment extends Component
{
  const allowsChildren = true;

  protected function render ()
  {
    $this->runChildren ();
  }

}

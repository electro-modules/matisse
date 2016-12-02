<?php
namespace Matisse\Exceptions;

use Matisse\Components\Base\Component;
use Matisse\Components\Text;
use PhpKit\WebConsole\Lib\Debug;

class ComponentException extends MatisseException
{
  public function __construct (Component $component = null, $msg = '', $deep = false, $previous = null)
  {
    // Prevent infinite recursion when this constructor throws an exception itself.
    static $nest = 0;

    if (ctype_alnum (substr ($msg, -1)))
      $msg .= '.';
    if (is_null ($component) || $nest++ == 2)
      parent::__construct ($msg);
    else {
      $class = Debug::typeInfoOf ($component);
      $id    = $o = '';
      try {
        $id    = $component->supportsProperties () && isset($component->props->id) ? $component->props->id : null;
        $props = isset($component->props) ? $component->props->getBeingAssigned () : [];
        $o     = $props ? self::properties ($props) : '';
        // Append a period, if applicable.

        $i = $this->inspect ($component, $deep);
        $o = (!$component->props || !$component->props->getAll () ? '' : "<h6>Properties</h6>$i") . $o;
      }
      catch (\Exception $e) {
      }

      $header = $component instanceof Text
        ? ""
        : ($id ? "Error on $class component <b>$id</b>" : "Error on a $class component.");
      $msg    = "<p>$header<p>$msg</p>" . ($o ? "<hr>$o" : '');
      parent::__construct ($msg, '', $previous);
    }
  }

  /**
   * Returns a formatted properties table.
   *
   * @param array $props
   * @return string
   */
  static private function properties (array $props)
  {
    return "<h6>Assigned properties</h6><table class=grid>
" . str_replace (["'", '...'], ["<i>'</i>", '<i>...</i>'], implode ('',
        map ($props, function ($v, $k) {
          return "<tr><th>$k<td>" .
                 (is_string ($v) ? "'" . htmlspecialchars (trimText ($v, 300, '...')) . "'" : Debug::toString ($v));
        })), $o) . "
</table>";
  }

}

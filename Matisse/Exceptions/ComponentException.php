<?php
namespace Selenia\Matisse\Exceptions;

use Selenia\Matisse\Components\Base\Component;

class ComponentException extends MatisseException
{
  public function __construct (Component $component = null, $msg = '', $deep = false)
  {
    if (is_null ($component))
      parent::__construct ($msg);
    else {
      $i     = $this->inspect ($component, $deep);
      $props = isset($component->props) ? $component->props->getBeingAssigned () : [];
      $o     = $props ? self::properties ($props) : '';
      $id    = $component->supportsProperties () && isset($component->props->id) ? $component->props->id : null;
      $class = typeInfoOf ($component);
      // Append a period, if applicable.
      if (ctype_alnum (substr ($msg, -1)))
        $msg .= '.';
      $o = (!$component->props || !$component->props->getAll ()
          ? ''
          : "<h6>Properties</h6>$i")
           . $o;

      parent::__construct (
        "<div>$msg</div><hr><h6>Component class</h6>$class$o</fieldset>",
        $id
          ?
          "Error on $class component <b>$id</b>"
          :
          "Error on a $class component"
      );
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
    return "<h6>Assigned properties</h6>
<table class=grid>
" . str_replace (["'", '...'], ["<i>'</i>", '<i>...</i>'], implode ('',
      map ($props, function ($v, $k) {
        return "<tr><th>$k<td>" . (is_string ($v) ? "'" . htmlspecialchars (trimText($v, 300, '...')) . "'" : var_export ($v, true));
      })), $o) . "
</table>";
  }

}

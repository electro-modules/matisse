<?php
namespace Matisse\Lib;

use Electro\Caching\Lib\CachingFileCompiler;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\Views\ViewEngineInterface;
use Electro\Interfaces\Views\ViewServiceInterface;
use Matisse\Components\Base\CompositeComponent;
use Matisse\Components\DocumentFragment;
use Matisse\Exceptions\MatisseException;
use Matisse\Parser\DocumentContext;
use Matisse\Parser\Parser;

class MatisseEngine implements ViewEngineInterface
{
  /**
   * The current rendering context.
   *
   * @var DocumentContext
   */
  private $context;
  /**
   * @var InjectorInterface
   */
  private $injector;
  /**
   * When set, the next compilation (and only that one) will generate a root component of the specified class.
   *
   * @var string|null
   */
  private $rootClass = null;
  /**
   * @var ViewServiceInterface
   */
  private $view;

  function __construct (ViewServiceInterface $view, DocumentContext $context, InjectorInterface $injector)
  {
    $this->view     = $view; // The view is always the owner if this engine, as long as the parameter is called $view
    $this->context  = clone $context;
    $this->injector = $injector;
  }

  function compile ($src)
  {
    if (!$this->context)
      throw new MatisseException ("No rendering context is set");

    // Create a compiled template.

    $class = $this->rootClass ?: DocumentFragment::class;
    /** @var DocumentFragment $root */
    $root = $this->injector->make ($class);
    $root->setContext ($this->context->makeSubcontext ());
    $this->rootClass = null;

    $base = $root;
    if ($root instanceof CompositeComponent) {
      $base = new DocumentFragment;
      $base->setContext ($this->context->makeSubcontext ());
    }

    $parser = new Parser;
    $parser->parse ($src, $base);

    if ($base !== $root)
      $root->setShadowDOM ($base);

    return $root;

//    echo "<div style='white-space:pre-wrap'>";
//    echo serialize ($root);exit;

  }

  function configure ($options)
  {
    $this->rootClass = get ($options, 'rootClass');
  }

  function loadFromCache (CachingFileCompiler $cache, $sourceFile)
  {
    global $usrlz_ctx, $usrlz_inj;

    // Preserve the current context.
    $prev_ctx = $usrlz_ctx;

    $usrlz_ctx = $this->context->makeSubcontext ();
    $usrlz_inj = $this->injector;

    $compiled = $cache->get ($sourceFile, function ($source) {
      return $this->compile ($source);
    });

    // Restore the current context.
    $usrlz_ctx = $prev_ctx;
    return $compiled;
  }

  function render ($compiled, $data = null)
  {
    // Matisse ignores the $data argument. The view model should be set by the CompositeComponent that owns the view,
    // and it is already set on the document context.

    /** @var DocumentFragment $compiled */
    return $compiled->getRendering ();
  }

}

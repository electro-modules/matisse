<?php
namespace Matisse\Lib;

use Electro\Caching\Lib\CachingFileCompiler;
use Electro\Interfaces\DI\InjectorInterface;
use Electro\Interfaces\Views\ViewEngineInterface;
use Electro\Interfaces\Views\ViewServiceInterface;
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

    $root = new DocumentFragment;
    $root->setContext ($this->context->makeSubcontext ());

    $parser = new Parser;
    $parser->parse ($src, $root);
    return $root;

//    echo "<div style='white-space:pre-wrap'>";
//    echo serialize ($root);exit;

  }

  function configure ($options)
  {
//    if (!$options instanceof Context)
//      throw new \InvalidArgumentException ("The argument must be an instance of " . formatClassName (Context::class));
//    $this->context = $options;
  }

  function loadFromCache (CachingFileCompiler $cache, $sourceFile)
  {
    global $usrlz_ctx, $usrlz_inj;

    $usrlz_ctx = $this->context->makeSubcontext ();
    $usrlz_inj = $this->injector;

    return $cache->get ($sourceFile, function ($source) {
      return $this->compile ($source);
    });
  }

  function render ($compiled, $data = null)
  {
    // Matisse ignores the $data argument. The view model should be set by the CompositeComponent that owns the view,
    // and it is already set on the document context.

    /** @var DocumentFragment $compiled */
    return $compiled->getRendering ();
  }

}

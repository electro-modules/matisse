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
use Selenia\Platform\Components\Base\PageComponent;

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
   * When set, loading the template will generate a root component of the specified class.
   * <p>When NULL, {@see DocumentFragment} is returned.
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
    $this->context  = $context;
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
  }

  function configure (array $options = [])
  {
    $this->rootClass = get ($options, 'rootClass')
      ?: (get ($options, 'page') ? PageComponent::class : DocumentFragment::class);
  }

  function loadFromCache (CachingFileCompiler $cache, $sourceFile)
  {
    global $usrlz_ctx, $usrlz_inj;

    // Preserve the current context.
    $prev_ctx = $usrlz_ctx;

    $usrlz_ctx = $this->context->makeSubcontext ();
    $usrlz_inj = $this->injector;

    $compiled = $cache->get ($sourceFile, function ($source) use ($sourceFile) {
      $root = $this->compile ($source);
      if ($root instanceof CompositeComponent)
        $root->templateUrl = $sourceFile;
      return $root;
    });

    // Restore the current context.
    $usrlz_ctx = $prev_ctx;

    if ($this->rootClass) {
      /** @var CompositeComponent $root */
      $root = $this->injector->make ($this->rootClass);
      $root->setContext ($this->context->makeSubcontext ());
      $root->setShadowDOM ($compiled);
      return $root;
    }

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
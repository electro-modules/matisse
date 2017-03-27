<?php

namespace Matisse\Components\Base;

use Electro\Exceptions\FatalException;
use Electro\Exceptions\Flash\FileException;
use Electro\Exceptions\FlashMessageException;
use Electro\Exceptions\FlashType;
use Electro\Http\Lib\Http;
use Electro\Interfaces\Http\RequestHandlerInterface;
use Electro\Interfaces\Views\ViewModelInterface;
use PhpKit\WebConsole\Lib\Debug;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionException;
use ReflectionObject;
use Selenia\Platform\Config\PlatformModule;

/**
 * The base class for components that are standalone HTML document fragments.
 *
 * <p>If the component represents a full HTML page, it should extend {@see PageComponent} instead.
 */
class HttpAwareComponent extends CompositeComponent implements RequestHandlerInterface
{
  /**
   * @var array|Object The page's data model.
   */
  public $model;
  /**
   * @var ServerRequestInterface This is always available for page components, and it is not injected.
   */
  public $request;
  /**
   * If set to true, the view will be rendered on the POST request without a redirection taking place.
   *
   * @var bool
   */
  protected $renderOnAction = false;
  /**
   * @var ResponseInterface
   */
  protected $response;

  /**
   * Performs the main execution sequence.
   *
   * @param ServerRequestInterface $request
   * @param ResponseInterface      $response
   * @param callable               $next
   * @return ResponseInterface
   * @throws FatalException
   * @throws FileException
   * @throws FlashMessageException
   */
  function __invoke (ServerRequestInterface $request, ResponseInterface $response, callable $next)
  {
    $this->request  = $request;
    $this->response = $response;

    $this->initialize (); //custom setup

    $this->model ();

    switch ($this->request->getMethod ()) {
      /** @noinspection PhpMissingBreakStatementInspection */
      case 'POST':
        // Perform the requested action.
        $res = $this->doFormAction ();
        if ($res) {
          if ($res instanceof ResponseInterface)
            throw new FatalException (sprintf ("Invalid HTTP response type: %s<p>Expected: <kbd>ResponseInterface</kbd>",
              Debug::typeInfoOf ($res)));
          $response = $res;
        }
        if (!$this->renderOnAction) {
          if (!$res)
            $response = $this->autoRedirect ();
          break;
        }
      case 'GET':
        // Render the component.
        $response->getBody ()->write ((string)$this);
    }
    $this->finalize ($response);
    return $response;
  }

  /**
   * Retrieves a route parameter from the current HTTP request.
   *
   * ><p>**Note:** this is a shortcut method. You can also read parameters directly from the request object.
   *
   * @param string $name
   * @return string
   */
  function param ($name)
  {
    return $this->request->getAttribute ("@$name");
  }

  /**
   * Retrieves a list of route parameters from the current HTTP request.
   *
   * ><p>**Note:** this is a shortcut method. You can also read parameters directly from the request object.
   *
   * ><p>**Hint:** you can use the `list` operator to assign the result to multiple variables.
   * ><p>Ex:
   * ```
   *   list ($a,$b) = $this->params ('a','b');
   * ```
   *
   * @param string[] $names
   * @return string[]
   */
  function params (...$names)
  {
    return map ($names, function ($name) { return $this->request->getAttribute ("@$name"); });
  }

  function setupView ()
  {
    parent::setupView ();

    $this->context->getFilterHandler ()->registerFallbackHandler ($this);
  }

  /**
   * Sets a reference to the model on the view model, allowing the view to access the model for redndering.
   */
  protected function afterPreRun ()
  {
    parent::afterPreRun ();
    $this->getViewModel ()->model = $this->model;
  }

  /**
   * Override to implement an auto-redirection.
   *
   * @return ResponseInterface
   */
  protected function autoRedirect ()
  {
    return $this->response;
  }

  /**
   * Invokes the right controller method in response to the POST request's specified action.
   *
   * @return ResponseInterface|null
   * @throws FlashMessageException
   * @throws FileException
   */
  protected function doFormAction ()
  {
//    if (count ($_POST) == 0 && count ($_FILES) == 0)
//      throw new FileException(FileException::FILE_TOO_BIG, ini_get ('upload_max_filesize'));
    $this->getActionAndParam ($action, $param);
    $class = new ReflectionObject ($this);
    try {
      $method = $class->getMethod ('action_' . $action);
    }
    catch (ReflectionException $e) {
      throw new FlashMessageException('Class <b>' . $class->getName () . "</b> can't handle action <b>$action</b>.",
        FlashType::ERROR);
    }
    return $method->invoke ($this, $param);
  }

  /**
   * Override to do something after the response has been generated.
   *
   * @param ResponseInterface $response
   */
  protected function finalize (ResponseInterface $response)
  {
    // no op
  }

  /**
   * Utility method for retrieving the value of a form field submitted via a `application/x-www-form-urlencoded` or a
   * `multipart/form-data` POST request.
   *
   * @param string $name
   * @param        mixed [optional] $def
   * @return mixed
   */
  protected function formField ($name, $def = null)
  {
    return Http::field ($this->request, $name, $def);
  }

  protected function getActionAndParam (&$action, &$param)
  {
    $action = get ($_REQUEST, PlatformModule::ACTION_FIELD, '');
    if (preg_match ('#(\w*):(.*)#', $action, $match)) {
      $action = $match[1];
      $param  = $match[2];
    }
    else $param = null;
  }

  /**
   * Initializes the controller.
   * Override to implement initialization code that should run before all other processing on the controller.
   * Make sure to always call the parent function.
   */
  protected function initialize ()
  {
  }

  /**
   * Override to set the model for the controller / view.
   *
   * <p>You should usually set the 'model' property of the class.
   *
   * > The model will be available on all request methods (GET, POST, etc).
   */
  protected function model ()
  {
    // override
  }

  /**
   * {@inheritdoc}
   *
   * <p>Note:
   * > View models are available only on GET requests.
   */
  protected function viewModel (ViewModelInterface $viewModel)
  {
    //Override.
  }

}

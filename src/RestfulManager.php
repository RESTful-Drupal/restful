<?php

/**
 * @file
 * Contains \Drupal\restful\RestfulManager.
 */

namespace Drupal\restful;

use Drupal\restful\Formatter\FormatterManager;
use Drupal\restful\Formatter\FormatterManagerInterface;
use Drupal\restful\Http\Request;
use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Http\Response;
use Drupal\restful\Http\ResponseInterface;
use Drupal\restful\Resource\ResourceManager;
use Drupal\restful\Resource\ResourceManagerInterface;

class RestfulManager {

  /**
   * The front controller callback function.
   */
  const FRONT_CONTROLLER_CALLBACK = 'restful_menu_process_callback';

  /**
   * The front controller access callback function.
   */
  const FRONT_CONTROLLER_ACCESS_CALLBACK = 'restful_menu_access_callback';

  /**
   * The request object.
   *
   * @var RequestInterface
   */
  protected $request;

  /**
   * The response object.
   *
   * @var ResponseInterface
   */
  protected $response;

  /**
   * The resource manager.
   *
   * @var ResourceManagerInterface
   */
  protected $resourceManager;

  /**
   * The formatter manager.
   *
   * @var FormatterManagerInterface
   */
  protected $formatterManager;

  /**
   * Accessor for the request.
   *
   * @return RequestInterface
   *   The request object.
   */
  public function getRequest() {
    return $this->request;
  }

  /**
   * Mutator for the request.
   *
   * @param RequestInterface $request
   *   The request object.
   */
  public function setRequest(RequestInterface $request) {
    $this->request = $request;
  }

  /**
   * Accessor for the response.
   *
   * @return ResponseInterface
   *   The response object.
   */
  public function getResponse() {
    return $this->response;
  }

  /**
   * Mutator for the response.
   *
   * @param ResponseInterface $response
   *   The response object.
   */
  public function setResponse(ResponseInterface $response) {
    $this->response = $response;
  }

  /**
   * Accessor for the resource manager.
   *
   * @return ResourceManagerInterface
   */
  public function getResourceManager() {
    return $this->resourceManager;
  }

  /**
   * Mutator for the resource manager.
   *
   * @param ResourceManagerInterface $resourceManager
   */
  public function setResourceManager(ResourceManagerInterface $resourceManager) {
    $this->resourceManager = $resourceManager;
  }

  /**
   * Accessor for the formatter manager.
   *
   * @return FormatterManagerInterface
   */
  public function getFormatterManager() {
    return $this->formatterManager;
  }

  /**
   * Mutator for the formatter manager.
   *
   * @param FormatterManagerInterface $formatterManager
   */
  public function setFormatterManager(FormatterManagerInterface $formatterManager) {
    $this->formatterManager = $formatterManager;
  }

  /**
   * Constructor.
   */
  public function __construct(RequestInterface $request, ResponseInterface $response, ResourceManagerInterface $resource_manager, FormatterManagerInterface $formatter_manager) {
    // Init the properties.
    $this->request = $request;
    $this->response = $response;
    $this->resourceManager = $resource_manager;
    $this->formatterManager = $formatter_manager;
  }

  /**
   * Factory method.
   *
   * @return RestfulManager
   *   The newly created manager.
   */
  public static function createFromGlobals() {
    $request = Request::createFromGlobals();
    $response = Response::create();
    $resource_manager = new ResourceManager($request);
    $formatter_manager = new FormatterManager();

    return new static($request, $response, $resource_manager, $formatter_manager);
  }

  /**
   * Processes the request to produce a response.
   *
   * @return Response
   *   The response being sent back to the consumer via the menu callback.
   */
  public function process() {
    // Gets the appropriate resource plugin (based on the request object). That
    // plugin processes the request according to the REST principles.
    $data = $this->resourceManager->process();

    // Formats the data based on the negotiatied output format. It accesses to
    // the request information via the service container.
    $body = $this->formatterManager->format($data);

    // Prepares the body for the response. At this point all headers have been
    // added to the response.
    $this->response->setContent($body);

    // The menu callback function is in charge of adding all the headers and
    // returning the body.
    return $this->response;
  }

  /**
   * Checks if the passed in request belongs to RESTful.
   *
   * @param RequestInterface $request
   *   The path to check.
   *
   * @return bool
   *   TRUE if the path belongs to RESTful.
   */
  public static function isRestfulPath(RequestInterface $request) {
    return ResourceManager::getPageCallback($request->getPath(FALSE)) == static::FRONT_CONTROLLER_CALLBACK;
  }

}

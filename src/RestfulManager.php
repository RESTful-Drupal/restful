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
   * Accessor for the request.ç
   *
   * @return RequestInterface
   */
  public function getRequest() {
    return $this->request;
  }

  /**
   * Mutator for the request.
   *
   * @param RequestInterface $request
   */
  public function setRequest($request) {
    $this->request = $request;
  }

  /**
   * Accessor for the response.
   *
   * @return ResponseInterface
   */
  public function getResponse() {
    return $this->response;
  }

  /**
   * Mutator for the response.
   *
   * @param ResponseInterface $response
   */
  public function setResponse($response) {
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
  public function setResourceManager($resourceManager) {
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
  public function setFormatterManager($formatterManager) {
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
   */
  public static function createFromGlobals() {
    $request = Request::createFromGlobals();
    // TODO: Implement the response class.
    $response = Response::create();
    // TODO: Implement the ResourceManager class.
    $resource_manager = ResourceManager::create($request);
    // TODO: Make the formatter manager independent from the resource plugin.
    $formatter_manager = new FormatterManager();

    return static($request, $response, $resource_manager, $formatter_manager);
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
    $this->response->setBody($body);

    // The menu callback function is in charge of adding all the headers and
    // returning the body.
    return $this->response;
  }

}

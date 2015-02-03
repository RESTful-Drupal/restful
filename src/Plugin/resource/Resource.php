<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Resource.
 */

namespace Drupal\restful\Plugin\resource;

use Drupal\Component\Plugin\PluginBase;
use Drupal\restful\Exception\BadRequestException;
use Drupal\restful\Exception\ForbiddenException;
use Drupal\restful\Exception\GoneException;
use Drupal\restful\Exception\NotImplementedException;
use Drupal\restful\Exception\ServerConfigurationException;
use Drupal\restful\Http\HttpHeader;
use Drupal\restful\Http\Request;
use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Resource\ResourceManager;

abstract class Resource extends PluginBase implements ResourceInterface {

  /**
   * The string that separates multiple ids.
   */
  const IDS_SEPARATOR = ',';

  /**
   * The requested path.
   *
   * @var string
   */
  protected $path;

  /**
   * The current request.
   *
   * @var RequestInterface
   */
  protected $request;

  /**
   * The data provider.
   *
   * @var DataProviderInterface
   */
  protected $dataProvider;

  /**
   * The field definition object.
   *
   * @var ResourceFieldCollectionInterface
   */
  protected $fieldDefinitions;

  /**
   * {@inheritdoc}
   */
  public function getRequest() {
    if (isset($this->request)) {
      return $this->request;
    }
    if (!$this->request = $this->configuration['request']) {
      throw new ServerConfigurationException('Request object is not available for the Resource plugin.');
    }
    return $this->request;
  }

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    if (isset($this->path)) {
      return $this->path;
    }
    // This gives us the path without the RESTful prefix.
    $path = preg_quote($this->getRequest()->getPath(), '#');
    // Remove the version prefix.
    $this->path = preg_replace('#^v\d+(\.\d+)?/#', '', $path);
    return $this->path;
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(ResourceFieldCollectionInterface $field_definitions) {
    $this->fieldDefinitions = $field_definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function process() {
    $path = $this->getPath();
    // TODO: Throw exception from getControllerFromPath() method.
    if (!$method = $this->getControllerFromPath($path)) {
      throw new NotImplementedException(sprintf('There is no handler for "%s" on the path: %s', $this->getRequest()->getMethod(), $path));
    }

    // Now check if there is access for this method on this path.
    // TODO: Move this inside the $method callback.
    // TODO: This is the method the ResourceEntity uses to perform entity access checks.
    $this->access($method, $path);

    return ResourceManager::executeCallback($method, array($path));
  }

  /**
   * {@inheritdoc}
   */
  public static function contollersInfo() {
    // Provide sensible defaults for the HTTP methods. These methods (index,
    // create, view, update and delete) are not implemented in this layer but
    // they are guaranteed to exist because we are enforcing that all restful
    // resources are an instance of \RestfulDataProviderInterface.
    return array(
      '' => array(
        // GET returns a list of entities.
        Request::METHOD_GET => 'index',
        Request::METHOD_HEAD => 'index',
        // POST
        Request::METHOD_POST => 'create',
      ),
      // We don't know what the ID looks like, assume that everything is the ID.
      '^.*$' => array(
        Request::METHOD_GET => 'view',
        Request::METHOD_HEAD => 'view',
        Request::METHOD_PUT => 'replace',
        Request::METHOD_PATCH => 'update',
        Request::METHOD_DELETE => 'remove',
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function getControllers() {
    $controllers = array();
    foreach (static::contollersInfo() as $path => $method_info) {
      $controllers[$path] = array();
      foreach ($method_info as $http_method => $controller_info) {
        $controllers[$path][$http_method] = $controller_info;
        if (!is_array($controller_info)) {
          $controllers[$path][$http_method] = array('callback' => $controller_info);
        }
      }
    }

    return $controllers;
  }

  /**
   * {@inheritdoc}
   */
  public function index($path) {
    return $this->dataProvider->index();
  }

  /**
   * {@inheritdoc}
   */
  public function view($path) {
    // TODO: Compare this with 1.x logic.
    $ids = static::IDS_SEPARATOR ? explode(static::IDS_SEPARATOR, $path) :  array($path);
    return $this->dataProvider->viewMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function create($path) {
    // TODO: Compare this with 1.x logic.
    $object = $this->getRequest()->getParsedBody();
    return $this->dataProvider->create($object);
  }

  /**
   * {@inheritdoc}
   */
  public function update($path) {
    // TODO: Compare this with 1.x logic.
    $object = $this->getRequest()->getParsedBody();
    return $this->dataProvider->update($path, $object, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function replace($path) {
    // TODO: Compare this with 1.x logic.
    $object = $this->getRequest()->getParsedBody();
    return $this->dataProvider->update($path, $object, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function remove($path) {
    // TODO: Compare this with 1.x logic.
    $this->dataProvider->remove($path);
    restful()->getResponse()->getHeaders()->add(HttpHeader::create('Status', 204));
  }

  /**
   * Return the controller from a given path.
   *
   * @throws BadRequestException
   * @throws ForbiddenException
   * @throws GoneException
   *
   * @return string
   *   The appropriate method to call.
   *
   */
  protected function getControllerFromPath() {
    $path = $this->getPath();
    $method = $this->request->getMethod();

    $selected_controller = NULL;
    foreach ($this->getControllers() as $pattern => $controllers) {
      // Find the controllers for the provided path.
      if ($pattern != $path && !($pattern && preg_match('/' . $pattern . '/', $path))) {
        continue;
      }

      if ($controllers === FALSE) {
        // Method isn't valid anymore, due to a deprecated API endpoint.
        $params = array('@path' => $path);
        throw new GoneException(format_string('The path @path endpoint is not valid.', $params));
      }

      if (!isset($controllers[$method])) {
        $params = array('@method' => strtoupper($method));
        throw new BadRequestException(format_string('The http method @method is not allowed for this path.', $params));
      }

      // We found the controller, so we can break.
      $selected_controller = $controllers[$method];
      if (is_array($selected_controller)) {
        // If there is a custom access method for this endpoint check it.
        if (!empty($selected_controller['access callback']) && !ResourceManager::executeCallback(array($this, $selected_controller['access callback']), array($path))) {
          throw new ForbiddenException(format_string('You do not have access to this endpoint: @method - @path', array(
            '@method' => $method,
            '@path' => $path,
          )));
        }
        $selected_controller = $selected_controller['callback'];
      }
      break;
    }

    return $selected_controller;
  }

}

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
use Drupal\restful\Plugin\resource\DataProvider\DataProviderInterface;
use Drupal\restful\Plugin\resource\Field\ResourceFieldCollectionInterface;
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
   * Data provider factory.
   *
   * @return DataProviderInterface
   *   The data provider for this resource.
   *
   * @throws NotImplementedException
   */
  abstract protected function dataProviderFactory();

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
  public function getFieldDefinitions() {
    return $this->fieldDefinitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataProvider() {
    if (isset($this->dataProvider)) {
      return $this->dataProvider;
    }
    return $this->dataProviderFactory();
  }

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param ResourceFieldCollectionInterface $field_definitions
   *   The field definitions.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ResourceFieldCollectionInterface $field_definitions) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->fieldDefinitions = $field_definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function process() {
    $path = $this->getPath();

    return ResourceManager::executeCallback($this->getControllerFromPath($path), array($path));
  }

  /**
   * {@inheritdoc}
   *
   * Provide sensible defaults for the HTTP methods. These methods (index,
   * create, view, update and delete) are not implemented in this layer but
   * they are guaranteed to exist because we are enforcing that all restful
   * resources are an instance of \RestfulDataProviderInterface.
   */
  public static function contollersInfo() {
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
    return $this->getDataProvider()->index();
  }

  /**
   * {@inheritdoc}
   */
  public function view($path) {
    // TODO: Compare this with 1.x logic.
    $ids = static::IDS_SEPARATOR ? explode(static::IDS_SEPARATOR, $path) :  array($path);
    return $this->getDataProvider()->viewMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function create($path) {
    // TODO: Compare this with 1.x logic.
    $object = $this->getRequest()->getParsedBody();
    return $this->getDataProvider()->create($object);
  }

  /**
   * {@inheritdoc}
   */
  public function update($path) {
    // TODO: Compare this with 1.x logic.
    $object = $this->getRequest()->getParsedBody();
    return $this->getDataProvider()->update($path, $object, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function replace($path) {
    // TODO: Compare this with 1.x logic.
    $object = $this->getRequest()->getParsedBody();
    return $this->getDataProvider()->update($path, $object, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function remove($path) {
    // TODO: Compare this with 1.x logic.
    $this->getDataProvider()->remove($path);
    restful()
      ->getResponse()
      ->getHeaders()
      ->add(HttpHeader::create('Status', 204));
  }

  /**
   * Return the controller from a given path.
   *
   * @return callable
   *   A callable as expected by ResourceManager::executeCallback.
   *
   * @throws BadRequestException
   * @throws ForbiddenException
   * @throws GoneException
   * @throws NotImplementedException
   * @throws ServerConfigurationException
   *
   * @see ResourceManager::executeCallback().
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

      // Create the callable from the method string.
      if (!ResourceManager::isValidCallback($selected_controller)) {
        // This means that the provided value means to be a public method on the
        // current class.
        $selected_controller = array($this, $selected_controller);
      }
      break;
    }

    if (empty($selected_controller)) {
      throw new NotImplementedException(sprintf('There is no handler for "%s" on the path: %s', $this->getRequest()->getMethod(), $path));
    }

    return $selected_controller;
  }

}

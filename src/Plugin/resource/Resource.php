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
use Drupal\restful\Plugin\resource\Field\ResourceFieldCollection;
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
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->fieldDefinitions = ResourceFieldCollection::factory($this->publicFields());
  }

  /**
   * {@inheritdoc}
   */
  public function getAccount($cache = TRUE) {
    return user_load($GLOBALS['user']->uid);
  }

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
   * {@inheritdoc}
   */
  public function getResourceName() {
    $definition = $this->getPluginDefinition();
    return $definition['name'];
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
  public function controllersInfo() {
    return array(
      '' => array(
        // GET returns a list of entities.
        Request::METHOD_GET => 'index',
        Request::METHOD_HEAD => 'index',
        // POST.
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
  public function getControllers() {
    $controllers = array();
    foreach ($this->controllersInfo() as $path => $method_info) {
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
    $ids = static::IDS_SEPARATOR ? explode(static::IDS_SEPARATOR, $path) : array($path);

    // REST requires a canonical URL for every resource.
    $canonical_path = $this->getDataProvider()->canonicalPath($path);
    $this
      ->getRequest()
      ->getHeaders()
      ->add(HttpHeader::create('Link', $this->versionedUrl($canonical_path, array(), FALSE) . '; rel="canonical"'));

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
   * @see ResourceManager::executeCallback()
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
          throw new ForbiddenException(sprintf('You do not have access to this endpoint: %s - %s', $method, $path));
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

  /**
   * {@inheritdoc}
   */
  public function getVersion() {
    $plugin_definition = $this->getPluginDefinition();
    $version = array(
      'major' => $plugin_definition['majorVersion'],
      'minor' => $plugin_definition['minorVersion'],
    );
    return $version;
  }

  /**
   * {@inheritdoc}
   */
  public function versionedUrl($path = '', $options = array(), $version_string = TRUE) {
    // Make the URL absolute by default.
    $options += array('absolute' => TRUE);
    $plugin_definition = $this->getPluginDefinition();
    if (!empty($plugin_definition['menuItem'])) {
      $url = $plugin_definition['menuItem'] . '/' . $path;
      return url(rtrim($url, '/'), $options);
    }

    $base_path = variable_get('restful_hook_menu_base_path', 'api');
    $url = $base_path;
    if ($version_string) {
      $url .= '/v' . $plugin_definition['majorVersion'] . '.' . $plugin_definition['minorVersion'];
    }
    $url .= '/' . $plugin_definition['resource'] . '/' . $path;
    return url(rtrim($url, '/'), $options);
  }

  /**
   * {@inheritdoc}
   */
  public function access() {
    return $this->accessByAllowOrigin();
  }

  /**
   * Checks access based on the referer header and the allow_origin setting.
   *
   * @return bool
   *   TRUE if the access is granted. FALSE otherwise.
   */
  protected function accessByAllowOrigin() {
    // Check the referrer header and return false if it does not match the
    // Access-Control-Allow-Origin
    $referer = $this->getRequest()->getHeaders()->get('Referer')->getValueString();

    // If there is no allow_origin assume that it is allowed. Also, if there is
    // no referer then grant access since the request probably was not
    // originated from a browser.
    $plugin_definition = $this->getPluginDefinition();
    $origin = $plugin_definition['allowOrigin'];
    if (empty($origin) || $origin == '*' || !$referer) {
      return TRUE;
    }
    return strpos($referer, $origin) === 0;
  }

  /**
   * Public fields.
   *
   * @return array
   *   The field definition array.
   */
  abstract protected function publicFields();

}

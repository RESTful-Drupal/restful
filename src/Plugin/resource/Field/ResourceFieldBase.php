<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Field\ResourceFieldBase.
 */

namespace Drupal\restful\Plugin\resource\Field;

use Drupal\restful\Exception\ServerConfigurationException;
use Drupal\restful\Http\Request;
use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\Field\PublicFieldInfo\PublicFieldInfoBase;
use Drupal\restful\Plugin\resource\Field\PublicFieldInfo\PublicFieldInfoInterface;
use Drupal\restful\Plugin\resource\Field\PublicFieldInfo\PublicFieldInfoNull;
use Drupal\restful\Resource\ResourceManager;

abstract class ResourceFieldBase implements ResourceFieldInterface {

  /**
   * Return this value from public field access callbacks to allow access.
   */
  const ACCESS_ALLOW = 'allow';

  /**
   * Return this value from public field access callbacks to deny access.
   */
  const ACCESS_DENY = 'deny';

  /**
   * Return this value from public field access callbacks to not affect access.
   */
  const ACCESS_IGNORE = NULL;


  /**
   * Contains the public field name.
   */
  protected $publicName;

  /**
   * An array of callbacks to determine if user has access to the property. Note
   * that this callback is on top of the access provided by entity API, and is
   * used for convenience, where for example write operation on a property
   * should be denied only on certain request conditions. The Passed arguments
   * are:
   *   - op: The operation that access should be checked for. Can be "view" or
   *     "edit".
   *   - public_field_name: The name of the public field.
   *   - property_wrapper: The wrapped property.
   *   - wrapper: The wrapped entity.
   *
   * @var array
   */
  protected $accessCallbacks = array();

  /**
   * The entity property (e.g. "title", "nid").
   *
   * @var string
   */
  protected $property;

  /**
   * A callable callback to get a computed value. The wrapped entity is passed
   * as argument. Defaults To FALSE. The callback function receive as first
   * argument the entity.
   *
   * @var mixed
   */
  protected $callback;

  /**
   * An array of callbacks to perform on the returned value, or an array with
   * the object and method.
   *
   * @var array
   */
  protected $processCallbacks = array();

  /**
   * This property can be assigned only to an entity reference field. Array of
   * restful resources keyed by the target bundle. For example, if the field is
   * referencing a node entity, with "Article" and "Page" bundles, we are able
   * to map those bundles to their related resource. Items with bundles that
   * were not explicitly set would be ignored.
   *
   * It is also possible to pass an array as the value, with:
   *   - "name": The resource name.
   *   - "fullView": Determines if the referenced resource should be rendered,
   *   or just the referenced ID(s) to appear. Defaults to TRUE.
   *   array(
   *     // Shorthand.
   *     'article' => 'articles',
   *     // Verbose
   *     'page' => array(
   *       'name' => 'pages',
   *       'fullView' => FALSE,
   *     ),
   *   );
   *
   * @var array
   */
  protected $resource = array();

  /**
   * A generic array storage.
   *
   * @var array
   */
  protected $metadata = array();

  /**
   * The HTTP methods where this field applies.
   *
   * This replaces the create_or_update_passthrough feature. Defaults to all.
   *
   * @var array
   */
  protected $methods = array(
    RequestInterface::METHOD_GET,
    RequestInterface::METHOD_HEAD,
    RequestInterface::METHOD_POST,
    RequestInterface::METHOD_PUT,
    RequestInterface::METHOD_PATCH,
    RequestInterface::METHOD_OPTIONS,
  );

  /**
   * The request object to be used.
   *
   * @var RequestInterface
   */
  protected $request;

  /**
   * The field definition array.
   *
   * Use with caution.
   *
   * @var array
   */
  protected $definition = array();

  /**
   * Information about the field.
   *
   * @var PublicFieldInfoInterface
   */
  protected $publicFieldInfo;

  /**
   * Holds the field cardinality.
   *
   * @var int
   */
  protected $cardinality;

  /**
   * Get the request in the data provider.
   *
   * @return RequestInterface
   *   The request.
   */
  public function getRequest() {
    return $this->request;
  }

  /**
   * Set the request.
   *
   * @param RequestInterface $request
   *   The request.
   */
  public function setRequest(RequestInterface $request) {
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public function getPublicName() {
    return $this->publicName;
  }

  /**
   * {@inheritdoc}
   */
  public function setPublicName($public_name) {
    $this->publicName = $public_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessCallbacks() {
    return $this->accessCallbacks;
  }

  /**
   * {@inheritdoc}
   */
  public function setAccessCallbacks($access_callbacks) {
    $this->accessCallbacks = $access_callbacks;
  }

  /**
   * {@inheritdoc}
   */
  public function getProperty() {
    return $this->property;
  }

  /**
   * {@inheritdoc}
   */
  public function setProperty($property) {
    $this->property = $property;
  }

  /**
   * {@inheritdoc}
   */
  public function getCallback() {
    return $this->callback;
  }

  /**
   * {@inheritdoc}
   */
  public function setCallback($callback) {
    $this->callback = $callback;
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessCallbacks() {
    return $this->processCallbacks;
  }

  /**
   * {@inheritdoc}
   */
  public function setProcessCallbacks($process_callbacks) {
    $this->processCallbacks = $process_callbacks;
  }

  /**
   * {@inheritdoc}
   */
  public function getResource() {
    return $this->resource;
  }

  /**
   * {@inheritdoc}
   */
  public function setResource($resource) {
    $this->resource = $resource;
  }

  /**
   * {@inheritdoc}
   */
  public function getMethods() {
    return $this->methods;
  }

  /**
   * {@inheritdoc}
   */
  public function setMethods($methods) {
    foreach ($methods as $method) {
      if (Request::isValidMethod($method)) {
        throw new ServerConfigurationException(sprintf('The method %s in the field resource mapping is not valid.', $method));
      }
    }
    $this->methods = $methods;
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->publicName;
  }

  /**
   * {@inheritdoc}
   */
  public function isComputed() {
    return !$this->getProperty();
  }

  /**
   * {@inheritdoc}
   */
  public final static function isArrayNumeric(array $input) {
    $keys = array_keys($input);
    foreach ($keys as $key) {
      if (!ctype_digit((string) $key)) {
        return FALSE;
      }
    }
    return isset($keys[0]) ? $keys[0] == 0 : TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function addMetadata($key, $value) {
    $path = explode(':', $key);
    $leave = array_pop($path);
    $element = &$this->internalMetadataElement($key);

    $element[$leave] = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata($key) {
    $path = explode(':', $key);
    $leave = array_pop($path);
    $element = $this->internalMetadataElement($key);

    return isset($element[$leave]) ? $element[$leave] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinition() {
    return $this->definition;
  }

  /**
   * {@inheritdoc}
   */
  public function executeProcessCallbacks($value) {
    $process_callbacks = $this->getProcessCallbacks();
    if (!isset($value) || empty($process_callbacks)) {
      return $value;
    }
    foreach ($process_callbacks as $process_callback) {
      $value = ResourceManager::executeCallback($process_callback, array($value));
    }
    return $value;
  }

  /**
   * Returns the last array element from the nested namespace array.
   *
   * Searches in the metadata nested array the element in the data tree pointed
   * by the colon separated key. If the key goes through a non-existing path, it
   * initalize an empty array. The reference to that element is returned for
   * reading and writing purposes.
   *
   * @param string $key
   *   The namespaced key.
   *
   * @return array
   *   The reference to the array element.
   */
  protected function &internalMetadataElement($key) {
    // If there is a namespace, then use it to do nested arrays.
    $path = explode(':', $key);
    array_pop($path);
    $element = &$this->metadata;
    foreach ($path as $path_item) {
      if (!isset($element[$path_item])) {
        // Initialize an empty namespace.
        $element[$path_item] = array();
      }
      $element = $element[$path_item];
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getPublicFieldInfo() {
    return $this->publicFieldInfo;
  }

  /**
   * {@inheritdoc}
   */
  public function setPublicFieldInfo(PublicFieldInfoInterface $public_field_info) {
    $this->publicFieldInfo = $public_field_info;
  }

  /**
   * Basic auto discovery information.
   *
   * @return array
   *   The array of information ready to be encoded.
   */
  public function autoDiscovery() {
    return $this
      ->getPublicFieldInfo()
      ->prepare();
  }

  /**
   * Returns the basic discovery information for a given field.
   *
   * @param string $name
   *   The name of the public field.
   *
   * @return array
   *   The array of information ready to be encoded.
   */
  public static function emptyDiscoveryInfo($name) {
    $info = new PublicFieldInfoNull($name);
    return $info->prepare();
  }

}

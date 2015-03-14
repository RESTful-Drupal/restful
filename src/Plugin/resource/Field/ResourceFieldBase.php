<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Field\ResourceFieldBase.
 */

namespace Drupal\restful\Plugin\resource\Field;

use Drupal\restful\Exception\ServerConfigurationException;
use Drupal\restful\Http\Request;

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
   *   - "full_view": Determines if the referenced resource should be rendered,
   *   or just the referenced ID(s) to appear. Defaults to TRUE.
   *   array(
   *     // Shorthand.
   *     'article' => 'articles',
   *     // Verbose
   *     'page' => array(
   *       'name' => 'pages',
   *       'full_view' => FALSE,
   *     ),
   *   );
   *
   * @var array
   */
  protected $resource = array();

  /**
   * The HTTP methods where this field applies.
   *
   * This replaces the create_or_update_passthrough feature. Defaults to all.
   *
   * @var array
   */
  protected $methods = array(
    Request::METHOD_GET,
    Request::METHOD_HEAD,
    Request::METHOD_POST,
    Request::METHOD_PUT,
    Request::METHOD_OPTIONS,
  );

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
  public final static function isArrayNumeric(array $input) {
    foreach (array_keys($input) as $key) {
      if (!ctype_digit((string) $key)) {
        return FALSE;
      }
    }
    return TRUE;
  }

}

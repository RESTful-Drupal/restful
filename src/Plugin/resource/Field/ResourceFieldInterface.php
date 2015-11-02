<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Field\ResourceFieldInterface.
 */

namespace Drupal\restful\Plugin\resource\Field;

use Drupal\restful\Exception\IncompatibleFieldDefinitionException;
use Drupal\restful\Exception\ServerConfigurationException;
use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\DataInterpreter\DataInterpreterInterface;
use Drupal\restful\Plugin\resource\Field\PublicFieldInfo\PublicFieldInfoInterface;
use Drupal\restful\Resource\ResourceManager;

interface ResourceFieldInterface {

  /**
   * @return mixed
   */
  public function getPublicName();

  /**
   * @param mixed $public_name
   */
  public function setPublicName($public_name);

  /**
   * @return array
   */
  public function getAccessCallbacks();

  /**
   * @param array $access_callbacks
   */
  public function setAccessCallbacks($access_callbacks);

  /**
   * @return string
   */
  public function getProperty();

  /**
   * @param string $property
   */
  public function setProperty($property);

  /**
   * @return mixed
   */
  public function getCallback();

  /**
   * @param mixed $callback
   */
  public function setCallback($callback);

  /**
   * @return array
   */
  public function getProcessCallbacks();

  /**
   * @param array $process_callbacks
   */
  public function setProcessCallbacks($process_callbacks);

  /**
   * @return array
   */
  public function getResource();

  /**
   * @param array $resource
   */
  public function setResource($resource);

  /**
   * @return array
   */
  public function getMethods();

  /**
   * @param array $methods
   *
   * @throws ServerConfigurationException
   */
  public function setMethods($methods);

  /**
   * Checks if the current field is computed.
   *
   * @return bool
   *   TRUE if the field is computed.
   */
  public function isComputed();

  /**
   * Helper method to determine if an array is numeric.
   *
   * @param array $input
   *   The input array.
   *
   * @return boolean
   *   TRUE if the array is numeric, false otherwise.
   */
  public static function isArrayNumeric(array $input);

  /**
   * Factory.
   *
   * @param array $field
   *   Contains the field values.
   * @param RequestInterface $request
   *   The request.
   *
   * @return ResourceFieldInterface
   *   The created field
   *
   * @throws ServerConfigurationException
   */
  public static function create(array $field, RequestInterface $request = NULL);

  /**
   * Gets the value for the field given a data source.
   *
   * @param DataInterpreterInterface $interpreter
   *   The data source object. Interacts with the data storage.
   *
   * @return mixed
   *   The value for the public field.
   *
   * @throws IncompatibleFieldDefinitionException
   */
  public function value(DataInterpreterInterface $interpreter);

  /**
   * Check access on property by the defined access callbacks.
   *
   * @param string $op
   *   The operation that access should be checked for. Can be "view" or "edit".
   *   Defaults to "edit".
   * @param DataInterpreterInterface $interpreter
   *   The data source representing the entity.
   *
   * @return bool
   *   TRUE if the current user has access to set the property, FALSE otherwise.
   *   The default implementation assumes that if no callback has explicitly
   *   denied access, we grant the user permission.
   */
  public function access($op, DataInterpreterInterface $interpreter);

  /**
   * Gets the ID of the resource field.
   *
   * @return string
   *   The ID.
   */
  public function id();

  /**
   * Adds the default values to the definitions array.
   */
  public function addDefaults();

  /**
   * Add metadata to the field.
   *
   * This is a general purpose metadata storage for the field to store other
   * things that are not specifically the field value.
   *
   * You can pass in a namespaced $key using a : as a delimiter. Namespaces will
   * result in nested arrays. That means that addMetadata('foo:bar:baz', 'oof')
   * will result in metadata['foo']['bar']['baz'] = 'oof'.
   *
   * @param string $key
   *   The metadata item identifier.
   * @param mixed $value
   *   The metadata value.
   */
  public function addMetadata($key, $value);

  /**
   * Add metadata to the field.
   *
   * This is a general purpose metadata storage for the field to store other
   * things that are not specifically the field value.
   *
   * @param string $key
   *   The metadata item identifier.
   *
   * @return mixed
   *   The metadata value.
   */
  public function getMetadata($key);

  /**
   * Gets the value for the field given a data source.
   *
   * @param mixed $value
   *   The value for the field.
   * @param DataInterpreterInterface $interpreter
   *   The data source object. Interacts with the data storage.
   *
   * @throws IncompatibleFieldDefinitionException
   */
  public function set($value, DataInterpreterInterface $interpreter);

  /**
   * Executes the process callbacks.
   *
   * @param mixed $value
   *   The initial value.
   *
   * @return mixed
   *   The processed value.
   */
  public function executeProcessCallbacks($value);

  /**
   * Fetches the embedded identifier(s) for the current resource field, if any.
   *
   * @param DataInterpreterInterface $interpreter
   *   The data interpreter to get the compound ID.
   *
   * @return string|string[]
   *   An identifier or an array of identifiers for cardinality > 1. NULL if
   *   there is no identifier to be found.
   */
  public function compoundDocumentId(DataInterpreterInterface $interpreter);

  /**
   * Gets the value of a field and applies all process callbacks to it.
   *
   * @param DataInterpreterInterface $interpreter
   *   The data interpreter.
   *
   * @return mixed
   *   The value to render.
   */
  public function render(DataInterpreterInterface $interpreter);

  /**
   * Gets the cardinality of the wrapped field.
   *
   * @return int
   *   The number of potentially returned fields. Reuses field cardinality
   *   constants.
   */
  public function getCardinality();

  /**
   * Set the cardinality.
   *
   * @param int $cardinality
   *   The new cardinality.
   */
  public function setCardinality($cardinality);

  /**
   * Get the request in the data provider.
   *
   * @return RequestInterface
   *   The request.
   */
  public function getRequest();

  /**
   * Set the request.
   *
   * @param RequestInterface $request
   *   The request.
   */
  public function setRequest(RequestInterface $request);

  /**
   * Gets the original field definition as declared in Resource::publicFields().
   *
   * @return array
   *   The field definition.
   */
  public function getDefinition();

  /**
   * Gets the public field info object.
   *
   * @return PublicFieldInfoInterface
   *   The public field info object.
   */
  public function getPublicFieldInfo();

  /**
   * Gets the public field info object.
   *
   * @param PublicFieldInfoInterface $public_field_info
   *   The public field info object.
   */
  public function setPublicFieldInfo(PublicFieldInfoInterface $public_field_info);

}

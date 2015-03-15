<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Field\ResourceFieldInterface.
 */

namespace Drupal\restful\Plugin\resource\Field;

use Drupal\restful\Exception\ServerConfigurationException;
use Drupal\restful\Plugin\resource\DataSource\DataSourceInterface;

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
   *
   * @return ResourceFieldInterface
   *   The created field
   *
   * @throws ServerConfigurationException
   */
  public static function create(array $field);

  /**
   * Gets the value for the field given a data source.
   *
   * @param DataSourceInterface $source
   *   The data source object. Interacts with the data storage.
   *
   * @return mixed
   *   The value for the public field.
   */
  public function value(DataSourceInterface $source);

  /**
   * Check access on property by the defined access callbacks.
   *
   * @param string $op
   *   The operation that access should be checked for. Can be "view" or "edit".
   *   Defaults to "edit".
   * @param DataSourceInterface $source
   *   The data source representing the entity.
   *
   * @return bool
   *   TRUE if the current user has access to set the property, FALSE otherwise.
   *   The default implementation assumes that if no callback has explicitly
   *   denied access, we grant the user permission.
   */
  public function access($op, DataSourceInterface $source);

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

}

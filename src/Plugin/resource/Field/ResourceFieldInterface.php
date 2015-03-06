<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Field\ResourceFieldInterface.
 */

namespace Drupal\restful\Plugin\resource\Field;

use Drupal\restful\Exception\ServerConfigurationException;

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

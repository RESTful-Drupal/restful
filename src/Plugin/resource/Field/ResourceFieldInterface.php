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
   * @param mixed $publicName
   */
  public function setPublicName($publicName);

  /**
   * @return array
   */
  public function getAccessCallbacks();

  /**
   * @param array $accessCallbacks
   */
  public function setAccessCallbacks($accessCallbacks);

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
   * @param array $processCallbacks
   */
  public function setProcessCallbacks($processCallbacks);

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
   * @param array $fields
   *   Contains the field values.
   *
   * @return ResourceFieldInterface
   *
   * @throws ServerConfigurationException
   */
  public static function create(array $fields);

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

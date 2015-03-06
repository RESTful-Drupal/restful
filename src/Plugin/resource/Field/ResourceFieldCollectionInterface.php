<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Field\ResourceFieldCollectionInterface.
 */

namespace Drupal\restful\Plugin\resource\Field;

interface ResourceFieldCollectionInterface extends \Iterator, \Countable {

  /**
   * Factory.
   *
   * Creates the collection and each one of the field resource fields in it
   * based on the configuration array.
   *
   * @param array $fields
   *   An array of field mappings.
   *
   * @return ResourceFieldCollectionInterface
   *   The newly created object.
   */
  public static function factory(array $fields = array());

  /**
   * Factory.
   *
   * Creates the collection based on the implementation of the static::getInfo
   * method.
   *
   * @return ResourceFieldCollectionInterface
   *   The newly created object.
   */
  public static function create();

  /**
   * Default get info implementation.
   *
   * This is the method the implementers need to overwrite in order to provide
   * their own field definitions.
   *
   * @return array
   *   The array with field mappings.
   */
  public static function getInfo();

}

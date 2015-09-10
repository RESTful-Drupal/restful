<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Field\ResourceFieldEntityInterface.
 */

namespace Drupal\restful\Plugin\resource\Field;

interface ResourceFieldEntityInterface extends ResourceFieldInterface {

  /**
   * Decorate the object.
   *
   * @param ResourceFieldInterface $decorated
   *   The decorated subject.
   */
  public function decorate(ResourceFieldInterface $decorated);

  /**
   * @return string
   */
  public function getSubProperty();

  /**
   * @param string $sub_property
   */
  public function setSubProperty($sub_property);

  /**
   * @return string
   */
  public function getFormatter();

  /**
   * @param array $formatter
   */
  public function setFormatter($formatter);

  /**
   * @return string
   */
  public function getWrapperMethod();

  /**
   * @param string $wrapper_method
   */
  public function setWrapperMethod($wrapper_method);

  /**
   * @return boolean
   */
  public function isWrapperMethodOnEntity();

  /**
   * @param boolean $wrapper_method_on_entity
   */
  public function setWrapperMethodOnEntity($wrapper_method_on_entity);

  /**
   * @return string
   */
  public function getColumn();

  /**
   * @param string $column
   */
  public function setColumn($column);

  /**
   * @return array
   */
  public function getImageStyles();

  /**
   * @param array $image_styles
   */
  public function setImageStyles($image_styles);

  /**
   * @return string
   */
  public function getEntityType();

  /**
   * @param string $entity_type
   */
  public function setEntityType($entity_type);

  /**
   * @return string
   */
  public function getBundle();

  /**
   * @param string $bundle
   */
  public function setBundle($bundle);
  /**
   * Get the image URLs based on the configured image styles.
   *
   * @param array $file_array
   *   The file array.
   * @param array $image_styles
   *   The list of image styles to use.
   *
   * @return array
   *   The input file array with an extra key for the image styles.
   */
  public static function getImageUris(array $file_array, $image_styles);

  /**
   * Checks if a given string represents a Field API field.
   *
   * @param string $name
   *   The name of the field/property.
   *
   * @return bool
   *   TRUE if it's a field. FALSE otherwise.
   */
  public static function propertyIsField($name);

  /**
   * Massage the value to set according to the format expected by the wrapper.
   *
   * @param mixed $value
   *   The value passed in the request.
   *
   * @return mixed
   *   The value to set using the wrapped property.
   */
  public function preprocess($value);

}

<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Field\PublicFieldInfo\PublicFieldInfoEntityInterface.
 */

namespace Drupal\restful\Plugin\resource\Field\PublicFieldInfo;

interface PublicFieldInfoEntityInterface extends PublicFieldInfoInterface {

  /**
   * Get allowed values for the form schema.
   *
   * Using Field API's "Options" module to get the allowed values.
   *
   * @return mixed
   *   The allowed values or NULL if none found.
   */
  public function getFormSchemaAllowedValues();

  /**
   * Get the form type for the form schema.
   *
   * Using Field API's "Options" module to get the allowed values.
   *
   * @return mixed
   *   The form element type.
   */
  public function getFormSchemaAllowedType();

}

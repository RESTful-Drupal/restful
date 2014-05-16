<?php

/**
 * @file
 * Contains RestfulTestTestsResource.
 */

class RestfulTestTestsResource extends RestfulEntityBase {

  /**
   * Overrides RestfulEntityBase::getPublicFields().
   */
  public function getPublicFields() {
    $public_fields = parent::getPublicFields();
    $public_fields['type'] = array(
      'property' => 'name',
      'wrapper_method' => 'getBundle',
      'wrapper_method_on_entity' => TRUE,
    );
    return $public_fields;
  }
}

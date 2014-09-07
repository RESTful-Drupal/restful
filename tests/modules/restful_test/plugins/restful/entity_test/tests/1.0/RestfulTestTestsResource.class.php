<?php

/**
 * @file
 * Contains RestfulTestTestsResource.
 */

class RestfulTestTestsResource extends RestfulEntityBase {

  /**
   * Overrides RestfulEntityBase::publicFieldsInfo().
   */
  public function publicFieldsInfo() {
    $public_fields = parent::publicFieldsInfo();
    $public_fields['type'] = array(
      'property' => 'name',
      'wrapper_method' => 'getBundle',
      'wrapper_method_on_entity' => TRUE,
    );
    return $public_fields;
  }
}

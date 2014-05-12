<?php

/**
 * @file
 * Contains RestfulTestEntityTestsResource__1_4.
 */

class RestfulTestEntityTestsResource__1_4 extends RestfulTestEntityTestsResource {

  /**
   * Overrides RestfulTestEntityTestsResource::getPublicFields().
   */
  public function getPublicFields() {
    $public_fields = parent::getPublicFields();

    $public_fields['process_callback'] = array(
      'wrapper_method' => 'label',
      'wrapper_method_on_entity' => TRUE,
      'process_callback' => array($this, 'invalidProcessCallback'),
    );

    return $public_fields;
  }
}

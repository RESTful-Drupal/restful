<?php

/**
 * @file
 * Contains RestfulTestMainResource__1_4.
 */

class RestfulTestMainResource__1_4 extends RestfulTestMainResource {

  /**
   * Overrides RestfulTestEntityTestsResource::publicFieldsInfo().
   */
  public function publicFieldsInfo() {
    $public_fields = parent::publicFieldsInfo();

    $public_fields['process_callback'] = array(
      'wrapper_method' => 'label',
      'wrapper_method_on_entity' => TRUE,
      'process_callback' => array($this, 'invalidProcessCallback'),
    );

    return $public_fields;
  }
}

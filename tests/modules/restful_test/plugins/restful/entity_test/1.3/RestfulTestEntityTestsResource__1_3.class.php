<?php

/**
 * @file
 * Contains RestfulTestEntityTestsResource__1_3.
 */

class RestfulTestEntityTestsResource__1_3 extends RestfulTestEntityTestsResource {

  /**
   * Overrides RestfulTestEntityTestsResource::getPublicFields().
   */
  public function getPublicFields() {
    $public_fields = parent::getPublicFields();

    $public_fields['callback'] = array(
      'callback' => array($this, 'invalidCallback'),
    );

    return $public_fields;
  }
}

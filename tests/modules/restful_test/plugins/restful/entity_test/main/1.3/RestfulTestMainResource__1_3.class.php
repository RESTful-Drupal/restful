<?php

/**
 * @file
 * Contains RestfulTestMainResource__1_3.
 */

class RestfulTestMainResource__1_3 extends RestfulTestMainResource {

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

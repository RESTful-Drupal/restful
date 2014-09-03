<?php

/**
 * @file
 * Contains RestfulTestMainResource__1_3.
 */

class RestfulTestMainResource__1_3 extends RestfulTestMainResource {

  /**
   * Overrides RestfulTestEntityTestsResource::publicFieldsInfo().
   */
  public function publicFieldsInfo() {
    $public_fields = parent::publicFieldsInfo();

    $public_fields['callback'] = array(
      'callback' => array($this, 'invalidCallback'),
    );

    return $public_fields;
  }
}

<?php

/**
 * @file
 * Contains RestfulExampleArticlesResource__1_1.
 */

class RestfulExampleArticlesResource__1_1 extends RestfulTestArticlesResource {

  /**
   * Overrides RestfulExampleArticlesResource::getPublicFields().
   */
  public function getPublicFields() {
    $public_fields = parent::getPublicFields();
    unset($public_fields['self']);
    return $public_fields;
  }
}

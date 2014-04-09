<?php

/**
 * @file
 * Contains RestfulTestArticlesResource__1_1.
 */

class RestfulTestArticlesResource__1_1 extends RestfulTestArticlesResource {

  /**
   * Overrides RestfulTestArticlesResource::getPublicFields().
   */
  public function getPublicFields() {
    $public_fields = parent::getPublicFields();
    unset($public_fields['self']);
    return $public_fields;
  }
}

<?php

/**
 * @file
 * Contains RestfulExampleArticlesResource__1_1.
 */

class RestfulExampleArticlesResource__1_1 extends RestfulExampleArticlesResource {

  /**
   * Overrides RestfulExampleArticlesResource::publicFieldsInfo().
   */
  public function publicFieldsInfo() {
    $public_fields = parent::publicFieldsInfo();
    unset($public_fields['self']);
    return $public_fields;
  }
}

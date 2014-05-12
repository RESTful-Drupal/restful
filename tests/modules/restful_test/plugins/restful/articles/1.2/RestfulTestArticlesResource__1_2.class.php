<?php

/**
 * @file
 * Contains RestfulTestArticlesResource__1_2.
 */

class RestfulTestArticlesResource__1_2 extends RestfulExampleArticlesResource {

  /**
   * Overrides RestfulExampleArticlesResource::getPublicFields().
   */
  public function getPublicFields() {
    $public_fields = parent::getPublicFields();

    $public_fields['text_single'] = array(
      'property' => 'text_single',
    );

    return $public_fields;
  }
}

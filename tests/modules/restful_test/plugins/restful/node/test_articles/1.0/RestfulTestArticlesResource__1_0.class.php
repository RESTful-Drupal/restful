<?php

/**
 * @file
 * Contains RestfulTestArticlesResource__1_0.
 */

class RestfulTestArticlesResource__1_0 extends RestfulExampleArticlesResource {

  /**
   * Overrides RestfulExampleArticlesResource::getPublicFields().
   */
  public function getPublicFields() {
    $public_fields = parent::getPublicFields();

    $public_fields['body'] = array(
      'property' => 'body',
      'sub_property' => 'value',
    );

    return $public_fields;
  }
}

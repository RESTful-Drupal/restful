<?php

/**
 * @file
 * Contains RestfulExampleArticlesResource__1_5.
 */

class RestfulExampleArticlesResource__1_6 extends RestfulEntityBaseNode {

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

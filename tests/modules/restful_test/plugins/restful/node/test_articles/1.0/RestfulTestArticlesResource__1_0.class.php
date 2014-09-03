<?php

/**
 * @file
 * Contains RestfulTestArticlesResource__1_0.
 */

class RestfulTestArticlesResource__1_0 extends RestfulExampleArticlesResource {

  /**
   * Overrides RestfulExampleArticlesResource::publicFieldsInfo().
   */
  public function publicFieldsInfo() {
    $public_fields = parent::publicFieldsInfo();

    $public_fields['body'] = array(
      'property' => 'body',
      'sub_property' => 'value',
    );

    return $public_fields;
  }
}

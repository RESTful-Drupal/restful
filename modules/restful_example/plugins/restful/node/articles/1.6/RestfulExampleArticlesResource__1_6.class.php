<?php

/**
 * @file
 * Contains RestfulExampleArticlesResource__1_6.
 */

class RestfulExampleArticlesResource__1_6 extends RestfulEntityBaseNode {

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

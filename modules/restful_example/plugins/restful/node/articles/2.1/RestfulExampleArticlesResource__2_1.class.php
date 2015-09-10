<?php

/**
 * @file
 * Contains RestfulExampleArticlesResource__2_1.
 */

class RestfulExampleArticlesResource__2_1 extends RestfulEntityBaseNode {

  /**
   * TODO: Edit the func name in the docs.
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

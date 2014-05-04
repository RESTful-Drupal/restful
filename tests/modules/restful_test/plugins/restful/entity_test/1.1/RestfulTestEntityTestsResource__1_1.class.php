<?php

/**
 * @file
 * Contains RestfulTestEntityTestsResource__1_1.
 */

class RestfulTestEntityTestsResource__1_1 extends RestfulTestEntityTestsResource {

  /**
   * Overrides RestfulTestEntityTestsResource::getPublicFields().
   */
  public function getPublicFields() {
    $public_fields = parent::getPublicFields();

    $public_fields['single'] = array(
      'property' => 'text_single',
      'sub_property' => 'value',
    );

    $public_fields['multiple'] = array(
      'property' => 'text_multiple',
      'sub_property' => 'value',
    );
    return $public_fields;
  }
}

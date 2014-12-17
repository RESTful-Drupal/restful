<?php

/**
 * @file
 * Contains RestfulTestTranslatableEntityResource__1_0.
 */

class RestfulTestTranslatableEntityResource__1_0 extends RestfulTestMainResource {

  /**
   * Overrides RestfulTestEntityTestsResource::publicFieldsInfo().
   */
  public function publicFieldsInfo() {
    $public_fields = parent::publicFieldsInfo();

    $public_fields['text_single'] = array(
      'property' => 'text_single',
    );

    $public_fields['text_multiple'] = array(
      'property' => 'text_multiple',
    );

    return $public_fields;
  }
}

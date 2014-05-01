<?php

/**
 * @file
 * Contains RestfulTestEntityTestsResource__1_2.
 */

class RestfulTestEntityTestsResource__1_2 extends RestfulTestEntityTestsResource {

  /**
   * Overrides RestfulTestEntityTestsResource::getPublicFields().
   */
  public function getPublicFields() {
    $public_fields = parent::getPublicFields();

    $public_fields['single'] = array(
      'property' => 'entity_reference_single',
      'wrapper_method' => 'label',
    );

    $public_fields['multiple'] = array(
      'property' => 'entity_reference_multiple',
      'wrapper_method' => 'label',
    );

    $public_fields['resource_single'] = array(
      'property' => 'entity_reference_single',
      'resource' => 'entity_tests',
    );

    $public_fields['resource_multiple'] = array(
      'property' => 'entity_reference_multiple',
      'resource' => 'entity_tests',
    );



    return $public_fields;
  }
}

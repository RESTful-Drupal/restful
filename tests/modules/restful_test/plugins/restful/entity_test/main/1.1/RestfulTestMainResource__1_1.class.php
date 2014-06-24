<?php

/**
 * @file
 * Contains RestfulTestMainResource__1_1.
 */

class RestfulTestMainResource__1_1 extends RestfulTestMainResource {

  /**
   * Overrides RestfulTestEntityTestsResource::getPublicFields().
   */
  public function getPublicFields() {
    $public_fields = parent::getPublicFields();

    // Determine if the wrapper should use a "sub property" on a text field
    // by checking if the "text_processing" is enabled.
    $instance = field_info_instance($this->getEntityType(), 'text_single', $this->getBundle());
    $public_fields['text_single'] = array(
      'property' => 'text_single',
      'sub_property' => $instance['settings']['text_processing'] ? 'value' : FALSE,
    );

    $instance = field_info_instance($this->getEntityType(), 'text_multiple', $this->getBundle());
    $public_fields['text_multiple'] = array(
      'property' => 'text_multiple',
      'sub_property' => $instance['settings']['text_processing'] ? 'value' : FALSE,
    );

    $public_fields['entity_reference_single'] = array(
      'property' => 'entity_reference_single',
      'wrapper_method' => 'getIdentifier',
    );

    $public_fields['entity_reference_multiple'] = array(
      'property' => 'entity_reference_multiple',
      'wrapper_method' => 'getIdentifier',
    );

    // Single entity reference field with "resource".
    $public_fields['entity_reference_single_resource'] = array(
      'property' => 'entity_reference_single',
      'resource' => array(
        'main' => 'main',
      ),
    );

    // Multiple entity reference field with "resource".
    $public_fields['entity_reference_multiple_resource'] = array(
      'property' => 'entity_reference_multiple',
      'resource' => array(
        'main' => 'main',
      ),
    );

    return $public_fields;
  }
}

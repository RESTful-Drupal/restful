<?php

/**
 * @file
 * Contains RestfulTestMainResource__1_1.
 */

class RestfulTestMainResource__1_1 extends RestfulTestMainResource {

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
      'discovery' => array(
        'info' => array(
          'label' => t('Text multiple'),
          'description' => t('This field holds different text inputs.'),
        ),
        'data' => array(
          'type' => 'string',
          'cardinality' => FIELD_CARDINALITY_UNLIMITED,
        ),
        'form_element' => array(
          'type' => 'textfield',
          'size' => 255,
          'placeholder' => t('This is helpful.'),
        ),
      ),
    );

    $public_fields['text_single_processing'] = array(
      'property' => 'text_single_processing',
      'sub_property' => 'value',
    );

    $public_fields['text_multiple_processing'] = array(
      'property' => 'text_multiple_processing',
      'sub_property' => 'value',
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
        'main' => array(
          'name' => 'main',
          'major_version' => 1,
          'minor_version' => 1,
        ),
      ),
    );

    // Multiple entity reference field with "resource".
    $public_fields['entity_reference_multiple_resource'] = array(
      'property' => 'entity_reference_multiple',
      'resource' => array(
        'main' => array(
          'name' => 'main',
          'major_version' => 1,
          'minor_version' => 1,
        ),
      ),
    );

    $public_fields['term_single'] = array(
      'property' => 'term_single',
      'sub_property' => 'tid',
    );

    $public_fields['term_multiple'] = array(
      'property' => 'term_multiple',
      'sub_property' => 'tid',
    );

    $public_fields['file_single'] = array(
      'property' => 'file_single',
      'process_callbacks' =>
        array(
          array($this, 'getFileId'),
        )
    );

    $public_fields['file_multiple'] = array(
      'property' => 'file_multiple',
      'process_callbacks' => array(
        array($this, 'getFilesId'),
      ),
    );

    $public_fields['image_single'] = array(
      'property' => 'image_single',
      'process_callbacks' => array(
        array($this, 'getFileId'),
      ),
    );

    $public_fields['image_multiple'] = array(
      'property' => 'image_multiple',
      'process_callbacks' => array(
        array($this, 'getFilesId'),
      ),
    );


    return $public_fields;
  }

  /**
   * Return the file ID from the file array.
   *
   * Since by default Entity API does not allow to get the file ID, we extract
   * it ourself in this preprocess callback.
   *
   * @param array $value
   *   The file array as retrieved by the wrapper.
   *
   * @return int
   *   The file ID.
   */
  protected function getFileId($value) {
    return $value['fid'];
  }

  /**
   * Return the files ID from the multiple files array.
   *
   * @param array $value
   *   Array of files array as retrieved by the wrapper.
   *
   * @return int
   *   Array with file IDs.
   */
  protected function getFilesId($value) {
    $return = array();
    foreach ($value as $file_array) {
      $return[] = $file_array['fid'];
    }

    return $return;
  }
}

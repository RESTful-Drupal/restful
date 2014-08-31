<?php

/**
 * @file
 * Contains RestfulTestArticlesResource__1_2.
 */

class RestfulTestArticlesResource__1_2 extends RestfulEntityBaseNode {

  /**
   * Overrides \RestfulEntityBase::getPublicFields().
   */
  public function getPublicFields() {
    $public_fields = parent::getPublicFields();

    $public_fields['body'] = array(
      'property' => 'body',
      'sub_property' => 'value',
    );

    // By checking that the field exists, we allow re-using this class on
    // different tests, where different fields exist.
    if (field_info_field('entity_reference_single')) {
      $public_fields['entity_reference_single'] = array(
        'property' => 'entity_reference_single',
        'resource' => array(
          'article' => 'test_articles',
        ),
      );
    }

    if (field_info_field('entity_reference_multiple')) {
      $public_fields['entity_reference_multiple'] = array(
        'property' => 'entity_reference_multiple',
        'resource' => array(
          'article' => 'test_articles',
        ),
      );
    }

    if (field_info_field('integer_single')) {
      $public_fields['integer_single'] = array(
        'property' => 'integer_single',
      );
    }

    if (field_info_field('integer_multiple')) {
      $public_fields['integer_multiple'] = array(
        'property' => 'integer_multiple',
      );
    }


    return $public_fields;
  }

}

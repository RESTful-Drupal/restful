<?php

/**
 * @file
 * Contains RestfulTestArticlesResource__1_2.
 */

class RestfulTestArticlesResource__1_2 extends RestfulEntityBaseNode {

  /**
   * Overrides \RestfulEntityBase::publicFieldsInfo().
   */
  public function publicFieldsInfo() {
    $public_fields = parent::publicFieldsInfo();

    $public_fields['created'] = array(
      'property' => 'created',
    );

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
          'article' => array(
            'name' => 'test_articles',
            'major_version' => 1,
            'minor_version' => 2,
          ),
        ),
      );
    }

    if (field_info_field('entity_reference_multiple')) {
      $public_fields['entity_reference_multiple'] = array(
        'property' => 'entity_reference_multiple',
        'resource' => array(
          'article' => array(
            'name' => 'test_articles',
            'major_version' => 1,
            'minor_version' => 2,
          ),
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

    if (variable_get('restful_test_reference_simple')) {
      $public_fields['user'] = array(
        'property' => 'author',
      );

      if (variable_get('restful_test_reference_resource')) {
        $public_fields['user']['resource'] = array('user' => 'users');
      }
    }

    return $public_fields;
  }

}

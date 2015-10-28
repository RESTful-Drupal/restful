<?php

/**
 * @file
 * Contains \Drupal\restful_test\Plugin\resource\node\test_article\v1\TestArticles__1_2.
 */

namespace Drupal\restful_test\Plugin\resource\node\test_article\v1;

use Drupal\restful\Plugin\resource\ResourceInterface;
use Drupal\restful\Plugin\resource\ResourceNode;

/**
 * Class TestArticles
 * @package Drupal\restful\Plugin\resource
 *
 * @Resource(
 *   name = "test_articles:1.2",
 *   resource = "test_articles",
 *   label = "Test Articles",
 *   description = "Export the article content type.",
 *   authenticationTypes = {
 *     "basic_auth",
 *     "cookie"
 *   },
 *   authenticationOptional = TRUE,
 *   dataProvider = {
 *     "entityType": "node",
 *     "bundles": {
 *       "article"
 *     },
 *   },
 *   majorVersion = 1,
 *   minorVersion = 2
 * )
 */
class TestArticles__1_2 extends ResourceNode implements ResourceInterface {

  /**
   * Overrides ResourceEntity::publicFields().
   */
  public function publicFields() {
    $public_fields = parent::publicFields();

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
          'name' => 'test_articles',
          'majorVersion' => 1,
          'minorVersion' => 2,
        ),
      );
    }

    if (field_info_field('entity_reference_multiple')) {
      $public_fields['entity_reference_multiple'] = array(
        'property' => 'entity_reference_multiple',
        'resource' => array(
          'name' => 'test_articles',
          'majorVersion' => 1,
          'minorVersion' => 2,
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
        $public_fields['user']['resource'] = array(
          'name' => 'users',
          'majorVersion' => 1,
          'minorVersion' => 0,
        );
      }
    }

    return $public_fields;
  }

}

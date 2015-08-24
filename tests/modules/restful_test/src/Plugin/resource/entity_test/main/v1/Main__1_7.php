<?php

/**
 * @file
 * Contains \Drupal\restful_test\Plugin\resource\entity_test\main\v1\Main__1_7.
 */

namespace Drupal\restful_test\Plugin\resource\entity_test\main\v1;

use Drupal\restful\Plugin\resource\ResourceInterface;

/**
 * Class Main__1_7
 * @package Drupal\restful_test\Plugin\resource
 *
 * @Resource(
 *   name = "main:1.7",
 *   resource = "main",
 *   label = "Main",
 *   description = "Export the entity test 'main' bundle.",
 *   authenticationTypes = {
 *     "basic_auth",
 *     "cookie"
 *   },
 *   dataProvider = {
 *     "entityType": "entity_test",
 *     "bundles": {
 *       "main"
 *     },
 *   },
 *   majorVersion = 1,
 *   minorVersion = 7
 * )
 */
class Main__1_7 extends Main__1_0 implements ResourceInterface {

  /**
   * Overrides ResourceEntity::publicFields().
   */
  protected function publicFields() {
    $public_fields = parent::publicFields();

    // Single entity reference field with "resource".
    $public_fields['entity_reference_single'] = array(
      'property' => 'entity_reference_single',
      'referencedIdProperty' => 'uuid',
      // Add a fake resource to get only the ID.
    );

    // Multiple entity reference field with "resource".
    $public_fields['entity_reference_multiple'] = array(
      'property' => 'entity_reference_multiple',
      'referencedIdProperty' => 'uuid',
      // Add a fake resource to get only the ID.
    );

    $public_fields['term_single'] = array(
      'property' => 'term_single',
      'referencedIdProperty' => 'uuid',
      // Add a fake resource to get only the ID.
    );

    $public_fields['term_multiple'] = array(
      'property' => 'term_multiple',
      'referencedIdProperty' => 'uuid',
      // Add a fake resource to get only the ID.
    );

    $public_fields['file_single'] = array(
      'property' => 'file_single',
      'class' => '\Drupal\restful\Plugin\resource\Field\ResourceFieldFileEntityReference',
      'referencedIdProperty' => 'uuid',
      // Add a fake resource to get only the ID.
    );

    $public_fields['file_multiple'] = array(
      'property' => 'file_multiple',
      'class' => '\Drupal\restful\Plugin\resource\Field\ResourceFieldFileEntityReference',
      'referencedIdProperty' => 'uuid',
      // Add a fake resource to get only the ID.
    );

    return $public_fields;
  }

}

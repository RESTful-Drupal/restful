<?php

/**
 * @file
 * Contains \Drupal\restful_test\Plugin\resource\entity_test\EntityTests__1_0.
 */

namespace Drupal\restful_test\Plugin\resource\entity_test;

use Drupal\restful\Plugin\resource\ResourceEntity;
use Drupal\restful\Plugin\resource\ResourceInterface;

/**
 * Class EntityTests__1_0
 * @package Drupal\restful_test\Plugin\resource
 *
 * @Resource(
 *   name = "entity_tests:1.0",
 *   resource = "entity_tests",
 *   label = "Entity tests",
 *   description = "Export the entity test with multiple bundles.",
 *   authenticationOptional = TRUE,
 *   dataProvider = {
 *     "entityType": "entity_test",
 *     "bundles": {
 *       "test",
 *       "main"
 *     },
 *   },
 *   majorVersion = 1,
 *   minorVersion = 0
 * )
 */
class EntityTests__1_0 extends ResourceEntity implements ResourceInterface {

  /**
   * {@inheritdoc}
   */
  protected function publicFields() {
    $public_fields = parent::publicFields();

    $public_fields['main_bundle'] = array(
      'property' => 'pid',
      'class' => '\Drupal\restful\Plugin\resource\Field\ResourceFieldEntityReference',
      'resource' => array(
        'name' => 'main',
        'majorVersion' => 1,
        'minorVersion' => 0,
      ),
    );
    $public_fields['tests_bundle'] = array(
      'property' => 'pid',
      'class' => '\Drupal\restful\Plugin\resource\Field\ResourceFieldEntityReference',
      'resource' => array(
        'name' => 'tests',
        'majorVersion' => 1,
        'minorVersion' => 0,
      ),
    );

    return $public_fields;
  }


}

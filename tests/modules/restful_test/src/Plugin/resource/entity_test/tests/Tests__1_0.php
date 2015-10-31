<?php

/**
 * @file
 * Contains \Drupal\restful_test\Plugin\resource\entity_test\tests\Tests__1_0.
 */

namespace Drupal\restful_test\Plugin\resource\entity_test\tests;
use Drupal\restful\Plugin\resource\ResourceEntity;
use Drupal\restful\Plugin\resource\ResourceInterface;

/**
 * Class Tests__1_0
 * @package Drupal\restful_test\Plugin\resource
 *
 * @Resource(
 *   name = "tests:1.0",
 *   resource = "tests",
 *   label = "Tests",
 *   description = "Export the entity test 'tests' bundle.",
 *   authenticationOptional = TRUE,
 *   dataProvider = {
 *     "entityType": "entity_test",
 *     "bundles": {
 *       "test"
 *     },
 *   },
 *   majorVersion = 1,
 *   minorVersion = 0
 * )
 */
class Tests__1_0 extends ResourceEntity implements ResourceInterface {

  /**
   * {@inheritdoc}
   */
  protected function publicFields() {
    return array(
      'type' => array(
        'wrapper_method' => 'getBundle',
        'wrapper_method_on_entity' => TRUE,
      ),
    ) + parent::publicFields();
  }


}

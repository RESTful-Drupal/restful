<?php

/**
 * @file
 * Contains \Drupal\restful_test\Plugin\resource\entity_test\main\v1\Main__1_6.
 */

namespace Drupal\restful_test\Plugin\resource\entity_test\main\v1;

use Drupal\restful\Plugin\resource\ResourceInterface;

/**
 * Class Main__1_6
 * @package Drupal\restful_test\Plugin\resource
 *
 * @Resource(
 *   name = "main:1.6",
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
 *   minorVersion = 6
 * )
 */
class Main__1_6 extends Main__1_1 implements ResourceInterface {

  /**
   * Overrides ResourceEntity::publicFields().
   */
  protected function publicFields() {
    $public_fields = parent::publicFields();

    $public_fields['entity_reference_single_resource']['resource']['name'] = 'test_articles';
    $public_fields['entity_reference_multiple_resource']['resource']['name'] = 'test_articles';

    return $public_fields;
  }

}

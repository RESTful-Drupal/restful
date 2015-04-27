<?php

/**
 * @file
 * Contains \Drupal\restful_test\Plugin\resource\entity_test\main\v1\Main__1_5.
 */

namespace Drupal\restful_test\Plugin\resource\entity_test\main\v1;

use Drupal\restful\Plugin\resource\ResourceEntity;
use Drupal\restful\Plugin\resource\ResourceInterface;

/**
 * Class Main__1_5
 * @package Drupal\restful_test\Plugin\resource
 *
 * @Resource(
 *   name = "main:1.5",
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
 *   minorVersion = 5
 * )
 */
class Main__1_5 extends ResourceEntity implements ResourceInterface {

  /**
   * Overrides ResourceEntity::publicFields().
   */
  protected function publicFields() {
    $public_fields = parent::publicFields();

    $public_fields['process_callbacks'] = array(
      'wrapper_method' => 'label',
      'wrapper_method_on_entity' => TRUE,
      'process_callbacks' => array(
        array($this, 'invalidProcessCallback'),
      ),
    );

    return $public_fields;
  }

}

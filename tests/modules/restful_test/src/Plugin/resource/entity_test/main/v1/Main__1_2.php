<?php

/**
 * @file
 * Contains \Drupal\restful_test\Plugin\resource\entity_test\main\v1\Main__1_2.
 */

namespace Drupal\restful_test\Plugin\resource\entity_test\main\v1;

use Drupal\restful\Plugin\resource\ResourceInterface;

/**
 * Class Main__1_2
 * @package Drupal\restful_test\Plugin\resource
 *
 * @Resource(
 *   name = "main:1.2",
 *   resource = "main",
 *   label = "Main",
 *   description = "Export the entity test 'main' bundle.",
 *   authenticationOptional = TRUE,
 *   dataProvider = {
 *     "entityType": "entity_test",
 *     "bundles": {
 *       "main"
 *     },
 *   },
 *   majorVersion = 1,
 *   minorVersion = 2
 * )
 */
class Main__1_2 extends Main__1_0 implements ResourceInterface {

  /**
   * Overrides ResourceEntity::publicFields().
   */
  protected function publicFields() {
    $public_fields = parent::publicFields();

    $public_fields['callback'] = array(
      'callback' => array($this, 'callback'),
    );

    $public_fields['process_callback_from_callback'] = array(
      'callback' => array($this, 'callback'),
      'process_callbacks' => array(
        array($this, 'processCallbackFromCallback'),
      ),
    );

    $public_fields['process_callback_from_value'] = array(
      'wrapper_method' => 'getIdentifier',
      'wrapper_method_on_entity' => TRUE,
      'process_callbacks' => array(
        array($this, 'processCallbackFromValue'),
      ),
    );

    return $public_fields;
  }

  /**
   * Return a computed value.
   */
  protected function callback() {
    return 'callback';
  }

  /**
   * Process a computed value.
   */
  protected function processCallbackFromCallback($value) {
    return $value . ' processed from callback';
  }

  /**
   * Process a property value.
   */
  protected function processCallbackFromValue($value) {
    return $value . ' processed from value';
  }

}

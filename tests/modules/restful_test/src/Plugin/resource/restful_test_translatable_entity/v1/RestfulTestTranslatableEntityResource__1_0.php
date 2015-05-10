<?php

/**
 * @file
 * Contains \Drupal\restful_test\Plugin\resource\restful_test_translatable_entity\v1\RestfulTestTranslatableEntityResource__1_0.
 */

namespace Drupal\restful_test\Plugin\resource\restful_test_translatable_entity\v1;

use Drupal\restful\Plugin\resource\ResourceInterface;
use Drupal\restful_test\Plugin\resource\entity_test\main\v1\Main__1_0;

/**
 * Class RestfulTestTranslatableEntityResource__1_0
 * @package Drupal\restful_test\Plugin\resource
 *
 * @Resource(
 *   name = "restful_test_translatable_entity:1.0",
 *   resource = "restful_test_translatable_entity",
 *   label = "Main",
 *   description = "Export the entity test 'main' bundle.",
 *   authenticationOptional = TRUE,
 *   dataProvider = {
 *     "entityType": "restful_test_translatable_entity",
 *     "bundles": {
 *       "restful_test_translatable_entity"
 *     },
 *   },
 *   majorVersion = 1,
 *   minorVersion = 0
 * )
 */
class RestfulTestTranslatableEntityResource__1_0 extends Main__1_0 implements ResourceInterface {

  /**
   * Overrides Main__1_0::publicFields().
   */
  protected function publicFields() {
    $public_fields = parent::publicFields();

    $public_fields['text_single'] = array(
      'property' => 'text_single',
    );

    $public_fields['text_multiple'] = array(
      'property' => 'text_multiple',
    );

    return $public_fields;
  }


}

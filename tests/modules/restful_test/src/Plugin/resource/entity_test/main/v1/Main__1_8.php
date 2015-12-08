<?php

/**
 * @file
 * Contains \Drupal\restful_test\Plugin\resource\entity_test\main\v1\Main__1_8.
 */

namespace Drupal\restful_test\Plugin\resource\entity_test\main\v1;

use Drupal\restful\Plugin\resource\DataInterpreter\DataInterpreterInterface;

/**
 * Class Main__1_8.
 *
 * @package Drupal\restful_test\Plugin\resource
 *
 * @Resource(
 *   name = "main:1.8",
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
 *   minorVersion = 8
 * )
 */
class Main__1_8 extends Main__1_0 {

  /**
   * Overrides ResourceEntity::publicFields().
   */
  protected function publicFields() {
    $public_fields = parent::publicFields();

    $class = get_class($this);
    $public_fields['random_rel'] = array(
      'callback' => $class . '::randomRelationship',
      'class' => '\Drupal\restful\Plugin\resource\Field\ResourceFieldReference',
      'resource' => array(
        'name' => 'db_query_test',
        'majorVersion' => 1,
        'minorVersion' => 0,
      ),
    );

    return $public_fields;
  }

  /**
   * Returns a random relationship.
   *
   * This serves as an example of a use case for the generic relationship.
   *
   * @param DataInterpreterInterface $interpreter
   *   The data interpreter.
   *
   * @return mixed
   *   The embeddable result.
   */
  public static function randomRelationship(DataInterpreterInterface $interpreter) {
    /* @var \Drupal\restful\Plugin\resource\ResourceInterface $handler */
    $handler = restful()->getResourceManager()->getPlugin('db_query_test:1.0');
    // This simbolizes some complex logic that gets a rendered resource.
    $id = static::complexCalculation();
    return $handler->getDataProvider()->view($id);
  }

  /**
   * Do a complex calculation.
   *
   * @return int
   *   The ID of the db_query_test.
   */
  protected static function complexCalculation() {
    return 1;
  }

}

<?php

/**
 * @file
 * Contains
 */

namespace Drupal\restful_test\Plugin\resource\db_query_test\v1;

use Drupal\restful\Plugin\resource\ResourceDbQuery;
use Drupal\restful\Plugin\resource\ResourceInterface;

/**
 * Class DbQueryTest__1_0
 * @package Drupal\restful_test\Plugin\resource
 *
 * @Resource(
 *   name = "db_query_test:1.0",
 *   resource = "db_query_test",
 *   label = "DB Query Test",
 *   description = "Expose the test table.",
 *   dataProvider = {
 *     "tableName": "restful_test_db_query",
 *     "idColumn": "id",
 *     "primary": "id",
 *     "idField": "id",
 *   },
 *   authenticationTypes = TRUE,
 *   authenticationOptional = TRUE,
 *   renderCache = {
 *     "render": true
 *   },
 *   majorVersion = 1,
 *   minorVersion = 0
 * )
 */
class DbQueryTest__1_0 extends ResourceDbQuery implements ResourceInterface {

  /**
   * {@inheritdoc}
   */
  protected function publicFields() {
    $fields = array();

    $fields['id'] = array('property' => 'id');
    $fields['string'] = array('property' => 'str_field');
    $fields['integer'] = array('property' => 'int_field');
    $fields['serialized'] = array('property' => 'serialized_field');

    return $fields;
  }

}

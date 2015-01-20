<?php

/**
 * @file
 * Contains RestfulDbQueryTestTable.
 */

class RestfulDbQueryTestTable extends \RestfulDataProviderDbQuery implements \RestfulDataProviderDbQueryInterface, \RestfulDataProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function publicFieldsInfo() {
    return array(
      'id' => array(
        'property' => 'id',
      ),
      'string' => array(
        'property' => 'str_field',
      ),
      'integer' => array(
        'property' => 'int_field',
      ),
      'serialized' => array(
        'property' => 'serialized_field',
      ),
    );
  }

}

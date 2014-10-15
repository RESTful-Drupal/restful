<?php

/**
 * @file
 * Contains \RestfulQueryVariable
 */

class RestfulQueryVariable extends \RestfulQuery implements \RestfulQueryInterface, \RestfulDataProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function publicFieldsInfo() {
    return array(
      'name' => array(
        'property' => 'name',
      ),
      'value' => array(
        'property' => 'value',
//        'process_callbacks' => array(
//          '\RestfulQuery::unserializeData',
//        ),
      ),
    );
  }

}

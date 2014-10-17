<?php

/**
 * @file
 * Contains \RestfulQueryVariable
 */

class RestfulQueryVariable extends \RestfulDataProviderDbQuery implements \RestfulDataProviderDbQueryInterface, \RestfulDataProviderInterface {

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
      ),
    );
  }

}

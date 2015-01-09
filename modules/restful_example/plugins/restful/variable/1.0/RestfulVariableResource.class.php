<?php

/**
 * @file
 * Contains \RestfulQueryVariable
 */

class RestfulVariableResource extends \RestfulDataProviderVariable {

  /**
   * {@inheritdoc}
   */
  public function publicFieldsInfo() {
    return array(
      'variable_name' => array(
        'property' => 'name',
      ),
      'variable_value' => array(
        'property' => 'value',
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access() {
    $account = $this->getAccount();
    return user_access('adminsiter site configuration', $account);
  }

}

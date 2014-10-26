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
        'process_callbacks' => array(
          'unserialize',
        ),
      ),
      'self' => array(
        'callback' => array($this, 'getSelf'),
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

  /**
   * Returns the URL to the endpoint result.
   *
   * @param stdClass $row
   *   The record from the database with a single variable.
   *
   * @return string
   *   The RESTful endpoint.
   */
  protected function getSelf($row) {
    $base_path = variable_get('restful_hook_menu_base_path', 'api');
    $version = $this->getVersion();
    return url($base_path . '/v' . $version['major'] . '.' . $version['minor'] . '/' . $this->getResourceName() . '/' . $row->name, array('absolute' => TRUE));
  }

}

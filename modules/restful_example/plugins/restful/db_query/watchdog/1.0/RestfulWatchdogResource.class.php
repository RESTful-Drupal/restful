<?php

/**
 * @file
 * Contains RestfulEntityBase.
 */

class RestfulWatchdogResource extends \RestfulDataProviderDbQuery implements \RestfulDataProviderDbQueryInterface {
  public function publicFieldsInfo() {

    $public_fields['log_id'] = array(
      'property' => 'wid',
    );

    $public_fields['log_type'] = array(
      'property' => 'type',
    );

    $public_fields['log_text'] = array(
      'property' => 'message',
    );

    $public_fields['log_variables'] = array(
      'property' => 'variables',
    );

    $public_fields['log_level'] = array(
      'property' => 'severity',
    );

    $public_fields['log_path'] = array(
      'property' => 'location',
    );

    return $public_fields;
  }

  /**
   * {@inheritdoc}
   */
  public function access() {
    $account = $this->getAccount();
    return user_access('view site reports', $account);
  }
}

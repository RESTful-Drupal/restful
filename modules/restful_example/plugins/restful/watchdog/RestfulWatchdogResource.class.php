<?php

/**
 * @file
 * Contains RestfulEntityBase.
 */

/**
 * An abstract implementation of RestfulEntityInterface.
 */
class RestFulWatchdogResource extends \RestfulDataProviderDbQuery implements \RestfulDataProviderDbQueryInterface {
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
      // Needs a process function for write contexts to serialize the data.
    );

    $public_fields['log_level'] = array(
      'property' => 'severity',
    );

    $public_fields['log_path'] = array(
      'property' => 'location',
    );

    return $public_fields;
  }
}

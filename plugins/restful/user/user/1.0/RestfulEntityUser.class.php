<?php

/**
 * @file
 * Contains RestfulEntityUser.
 */

class RestfulEntityUser extends \RestfulEntityBase {

  /**
   * Overrides RestfulEntityBase::getPublicFields().
   */
  public function getPublicFields() {
    $public_fields = parent::getPublicFields();
    $public_fields['id'] = array(
      'property' => 'uid',
    );

    $public_fields['name'] = array(
      'property' => 'name',
    );

    return $public_fields;
  }
}

<?php

/**
 * @file
 * Contains RestfulEntityBaseUser.
 */

class RestfulEntityBaseUser extends \RestfulEntityBase {

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

    $public_fields['mail'] = array(
      'property' => 'mail',
    );

    return $public_fields;
  }
}

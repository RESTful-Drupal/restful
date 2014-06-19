<?php

/**
 * @file
 * Contains RestfulEntityBaseUser.
 */

class RestfulEntityBaseUser extends \RestfulEntityBase {

  /**
   * Overrides \RestfulEntityBase::getPublicFields().
   */
  public function getPublicFields() {
    $public_fields = parent::getPublicFields();
    $public_fields['id'] = array(
      'property' => 'uid',
    );

    $public_fields['mail'] = array(
      'property' => 'mail',
    );

    return $public_fields;
  }

  /**
   * Overrides \RestfulEntityBase::getList().
   *
   * Make sure only privileged users may see a list of users.
   */
  public function getList($request = NULL, stdClass $account = NULL) {
    if (!user_access('administer users', $account) && !user_access('access user profiles', $account)) {
      throw new \RestfulForbiddenException('You do not have access to listing of users.');
    }
    return parent::getList($request, $account);
  }

  /**
   * Overrides \RestfulEntityBase::getQueryForList().
   *
   * Skip the anonymous user in listing.
   */
  public function getQueryForList($request, stdClass $account = NULL) {
    $query = parent::getQueryForList($request, $account);
    $query->entityCondition('entity_id', 0, '>');
    return $query;
  }
}

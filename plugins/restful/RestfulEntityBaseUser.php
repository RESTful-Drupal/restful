<?php

/**
 * @file
 * Contains RestfulEntityBaseUser.
 */

class RestfulEntityBaseUser extends \RestfulEntityBase {

  /**
   * Overrides \RestfulEntityBase::publicFieldsInfo().
   */
  public function publicFieldsInfo() {
    $public_fields = parent::publicFieldsInfo();
    $public_fields['id'] = array(
      'property' => 'uid',
    );

    $public_fields['mail'] = array(
      'property' => 'mail',
    );

    return $public_fields;
  }

  /**
   * Overrides parent::getEntityInfo().
   */
  public function getEntityInfo($type = NULL) {
    $info = parent::getEntityInfo($type);
    $info['entity keys']['label'] = 'name';
    return $info;
  }

  /**
   * Overrides \RestfulEntityBase::getList().
   *
   * Make sure only privileged users may see a list of users.
   */
  public function getList() {
    $account = $this->getAccount();
    if (!user_access('administer users', $account) && !user_access('access user profiles', $account)) {
      throw new \RestfulForbiddenException('You do not have access to listing of users.');
    }
    return parent::getList();
  }

  /**
   * Overrides \RestfulEntityBase::getQueryForList().
   *
   * Skip the anonymous user in listing.
   */
  public function getQueryForList() {
    $query = parent::getQueryForList();
    $query->entityCondition('entity_id', 0, '>');
    return $query;
  }

}

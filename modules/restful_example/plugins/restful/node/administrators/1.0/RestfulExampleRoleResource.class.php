<?php
/**
 * @file
 * Contains RestfulExampleRoleResource.
 */

class RestfulExampleRoleResource extends \RestfulEntityBaseMultipleBundles implements \RestfulEntityInterface {

  /**
   * Overrides \RestfulEntityBase::__construct().
   */
  public function __construct($plugin, \RestfulAuthenticationManager $auth_manager = NULL) {
    parent::__construct($plugin, $auth_manager);
    // Remove all other controllers since we only need to GET a list.
    $this->controllers = array(
      '' => array(
        'get' => 'getList',
      )
    );
  }

  /**
   * Overrides \RestfulEntityBase::publicFields().
   */
  public function getPublicFields() {
    $public_fields = parent::getPublicFields();
    $public_fields['type'] = array(
      'property' => 'type',
    );
    $public_fields['roles'] = array(
      'property' => 'author',
      'sub_property' => 'roles',
      'wrapper_method' => 'label',
    );
    return $public_fields;
  }

  /**
   * Overrides \RestfulEntityBase::getQueryForList().
   */
  public function getQueryForList($request, stdClass $account = NULL) {
    $query = parent::getQueryForList($request, $account);
    // Get the configured roles.
    $options = $this->getPluginInfo('options');

    // Get a list of role ids for the configured roles.
    $roles_list = user_roles();
    $selected_rids = array();
    foreach ($roles_list as $rid => $role) {
      if (in_array($role, $options['roles'])) {
        $selected_rids[] = $rid;
      }
    }
    if (empty($selected_rids)) {
      return $query;
    }

    // Get the list of user ids belonging to the selected roles.
    $uids = db_query('SELECT uid FROM {users_roles} WHERE rid IN (:rids)', array(
      ':rids' => $selected_rids,
    ))->fetchAllAssoc('uid');

    // Restrict the list of entities to the nodes authored by any user on the
    // list of users with the administrator role.
    if (!empty($uids)) {
      $query->propertyCondition('uid', array_keys($uids), 'IN');
    }

    return $query;
  }

}

<?php
/**
 * @file
 * Contains RestfulExampleRoleResource.
 */

class RestfulExampleRoleResource extends \RestfulEntityBase implements \RestfulEntityInterface {

  /**
   * Overrides \RestfulBase::controllersInfo().
   */
  public static function controllersInfo() {
    return array(
      '' => array(
        \RestfulInterface::GET => 'getList',
      ),
    );
  }

  /**
   * Overrides \RestfulEntityBase::publicFields().
   */
  public function publicFieldsInfo() {
    $public_fields = parent::publicFieldsInfo();
    $public_fields['type'] = array(
      'property' => 'type',
      'wrapper_method' => 'getBundle',
      'wrapper_method_on_entity' => TRUE,
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
  public function getQueryForList() {
    $query = parent::getQueryForList();
    // Get the configured roles.
    if (!$options = $this->getPluginKey('options')) {
      return $query;
    }

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

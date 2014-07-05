<?php


/**
 * @file
 * Contains RestfulEntityBaseNode.
 */

/**
 * A base implementation for "Node" entity type.
 */
class RestfulEntityBaseNode extends RestfulEntityBase {

  /**
   * Overrides RestfulEntityBase::getQueryForList().
   *
   * Expose only published nodes.
   */
  public function getQueryForList($request, stdClass $account = NULL) {
    $query = parent::getQueryForList($request, $account);
    $query->propertyCondition('status', NODE_PUBLISHED);
    return $query;
  }

  /**
   * Overrides RestfulEntityBase::entityPreSave().
   *
   * Set the node author and other defaults.
   */
  public function entityPreSave($entity, $request, stdClass $account) {
    if (!empty($entity->nid)) {
      // Node is already saved.
      return;
    }
    node_object_prepare($entity);
    $entity->uid = $account->uid;
  }

}

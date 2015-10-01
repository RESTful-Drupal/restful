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
  public function getQueryForList() {
    $query = parent::getQueryForList();
    $query->propertyCondition('status', NODE_PUBLISHED);
    return $query;
  }

  /**
    * Overrides RestfulEntityBase::getQueryCount().
    *
    * Expose only published nodes.
    */
  public function getQueryCount() {
    $query = parent::getQueryCount();
    $query->propertyCondition('status', NODE_PUBLISHED);
    return $query;
  }

  /**
   * Overrides RestfulEntityBase::entityPreSave().
   *
   * Set the node author and other defaults.
   */
  public function entityPreSave(\EntityMetadataWrapper $wrapper) {
    $node = $wrapper->value();
    if (!empty($node->nid)) {
      // Node is already saved.
      return;
    }
    node_object_prepare($node);
    $node->uid = $this->getAccount()->uid;
  }

}

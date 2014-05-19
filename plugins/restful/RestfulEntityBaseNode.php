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
  public function getQueryForList($request, $account) {
    $query = parent::getQueryForList($request, $account);
    $query->propertyCondition('status', NODE_PUBLISHED);
    return $query;
  }
}

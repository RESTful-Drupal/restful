<?php

/**
 * @file
 * Contains \Drupal\restful_example\Plugin\resource\comment\DataProviderComment.
 */

namespace Drupal\restful_example\Plugin\resource\comment;

use Drupal\restful\Plugin\resource\DataProvider\DataProviderEntity;
use Drupal\restful\Plugin\resource\DataProvider\DataProviderInterface;

class DataProviderComment  extends DataProviderEntity implements DataProviderInterface {

  /**
   * Overrides DataProviderEntity::setPropertyValues().
   *
   * Set nid and node type to a comment.
   *
   * Note that to create a comment with 'post comments' permission, apply a
   * patch on https://www.drupal.org/node/2236229
   */
  protected function setPropertyValues(\EntityDrupalWrapper $wrapper, $object, $replace = FALSE) {
    $comment = $wrapper->value();
    if (empty($comment->nid) && !empty($object['nid'])) {
      // Comment nid must be set manually, as the nid property setter requires
      // 'administer comments' permission.
      $comment->nid = $object['nid'];
      unset($object['nid']);

      // Make sure we have a bundle name.
      $node = node_load($comment->nid);
      $comment->node_type = 'comment_node_' . $node->type;
    }

    parent::setPropertyValues($wrapper, $object, $replace);
  }

  /**
   * Overrides DataProviderEntity::getQueryForList().
   *
   * Expose only published comments.
   */
  public function getQueryForList() {
    $query = parent::getQueryForList();
    $query->propertyCondition('status', COMMENT_PUBLISHED);
    return $query;
  }

  /**
   * Overrides DataProviderEntity::getQueryCount().
   *
   * Only count published comments.
   */
  public function getQueryCount() {
    $query = parent::getQueryCount();
    $query->propertyCondition('status', COMMENT_PUBLISHED);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function entityPreSave(\EntityDrupalWrapper $wrapper) {
    $comment = $wrapper->value();
    if (!empty($comment->cid)) {
      // Comment is already saved.
      return;
    }

    $comment->uid = $this->getAccount()->uid;
  }

}

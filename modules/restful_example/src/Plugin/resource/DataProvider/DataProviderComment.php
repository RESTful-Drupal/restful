<?php

/**
 * @file
 * Contains \Drupal\restful_example\Plugin\resource\DataProvider\DataProviderComment.
 */

namespace Drupal\restful_example\Plugin\resource\DataProvider;

use Drupal\restful\Plugin\resource\DataProvider\DataProviderEntity;
use Drupal\restful\Plugin\resource\DataProvider\DataProviderInterface;

class DataProviderComment  extends DataProviderEntity implements DataProviderInterface {

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

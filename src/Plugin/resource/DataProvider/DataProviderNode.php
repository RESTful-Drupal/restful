<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\DataProvider\DataProviderNode.
 */

namespace Drupal\restful\Plugin\resource\DataProvider;

/**
 * Class DataProviderNode.
 *
 * @package Drupal\restful\Plugin\resource\DataProvider
 */
class DataProviderNode extends DataProviderEntity implements DataProviderInterface {

  /**
   * Overrides DataProviderEntity::getQueryForList().
   *
   * Expose only published nodes, and of the current language.
   */
  public function getQueryForList() {
    $query = parent::getQueryForList();
    $query->propertyCondition('status', NODE_PUBLISHED);
    $query->propertyCondition('language', $this->getLangCode());
    return $query;
  }

  /**
   * Overrides DataProviderEntity::getQueryCount().
   *
   * Only count published nodes, and of the current language.
   */
  public function getQueryCount() {
    $query = parent::getQueryCount();
    $query->propertyCondition('status', NODE_PUBLISHED);
    $query->propertyCondition('language', $this->getLangCode());
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function entityPreSave(\EntityDrupalWrapper $wrapper) {
    $node = $wrapper->value();
    if (!empty($node->nid)) {
      // Node is already saved.
      return;
    }
    node_object_prepare($node);
    $node->uid = $this->getAccount()->uid;
  }

}

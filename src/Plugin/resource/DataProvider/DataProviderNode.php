<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\DataProvider\DataProviderNode.
 */

namespace Drupal\restful\Plugin\resource\DataProvider;


class DataProviderNode extends DataProviderEntity implements DataProviderInterface {

  /**
   * Overrides DataProviderEntity::getQueryForList().
   *
   * Expose only published nodes.
   */
  public function getQueryForList() {
    $query = parent::getQueryForList();
    $query->propertyCondition('status', NODE_PUBLISHED);
    return $query;
  }

}

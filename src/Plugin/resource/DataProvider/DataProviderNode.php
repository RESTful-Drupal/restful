<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\DataProvider\DataProviderNode.
 */

namespace Drupal\restful\Plugin\resource\DataProvider;


use Drupal\restful\Exception\BadRequestException;

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

  /**
   * Overrides DataProviderEntity::count().
   *
   * Only count published nodes.
   */
  public function count() {
    $query = $this->getEntityFieldQuery();

    // If we are trying to filter on a computed field, just ignore it and log an
    // exception.
    try {
      $this->queryForListFilter($query);
    }
    catch (BadRequestException $e) {
      watchdog_exception('restful', $e);
    }
    $query->propertyCondition('status', NODE_PUBLISHED);

    $this->addExtraInfoToQuery($query);

    return intval($query->count()->execute());
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

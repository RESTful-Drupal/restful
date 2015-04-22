<?php

/**
 * @file
 * Contains Drupal\restful\Plugin\resource\ResourceNode.
 */

namespace Drupal\restful\Plugin\resource;


class ResourceNode extends ResourceEntity implements ResourceInterface {

  /**
   * Overrides ResourceEntity::entityPreSave().
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

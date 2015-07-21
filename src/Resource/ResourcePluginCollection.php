<?php

/**
 * @file
 * Contains \Drupal\restful\Resource\ResourcePluginCollection
 */

namespace Drupal\restful\Resource;

use Drupal\Core\Plugin\DefaultLazyPluginCollection;

class ResourcePluginCollection extends DefaultLazyPluginCollection {

  /**
   * The key within the plugin configuration that contains the plugin ID.
   *
   * @var string
   */
  protected $pluginKey = 'name';

  /**
   * Overwrites LazyPluginCollection::get().
   */
  public function &get($instance_id) {
    /* @var \Drupal\restful\Plugin\resource\ResourceInterface $resource */
    $resource = parent::get($instance_id);

    // Allow altering the resource, this way we can read the resource's
    // definition to return a different class that is using composition.
    drupal_alter('restful_resource', $resource);
    $resource = $resource->isEnabled() ? $resource : NULL;
    return $resource;
  }

}

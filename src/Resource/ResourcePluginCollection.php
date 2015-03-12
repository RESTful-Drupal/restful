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
  protected $pluginKey = 'resource';

}

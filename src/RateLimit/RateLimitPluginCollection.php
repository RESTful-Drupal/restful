<?php

/**
 * @file
 * Contains \Drupal\restful\RateLimit\RateLimitPluginCollection
 */

namespace Drupal\restful\RateLimit;

use Drupal\Core\Plugin\DefaultLazyPluginCollection;

class RateLimitPluginCollection extends DefaultLazyPluginCollection {

  /**
   * The key within the plugin configuration that contains the plugin ID.
   *
   * @var string
   */
  protected $pluginKey = 'name';

}

<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\RateLimitPluginManager.
 */

namespace Drupal\restful\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\plug\Util\Module;

class RateLimitPluginManager extends DefaultPluginManager {

  /**
   * Constructs RateLimitPluginManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \DrupalCacheInterface $cache_backend
   *   Cache backend instance to use.
   */
  public function __construct(\Traversable $namespaces, \DrupalCacheInterface $cache_backend) {
    parent::__construct('Plugin/rate_limit', $namespaces, 'Drupal\restful\Plugin\rate_limit\RateLimitInterface', '\Drupal\restful\Annotation\RateLimit');
    $this->setCacheBackend($cache_backend, 'rate_limit_plugins');
    $this->alterInfo('rate_limit_plugin');
  }

  /**
   * RateLimitPluginManager factory method.
   *
   * @param string $bin
   *   The cache bin for the plugin manager.
   *
   * @return RateLimitPluginManager
   *   The created manager.
   */
  public static function create($bin = 'cache') {
    return new static(Module::getNamespaces(), _cache_get_object($bin));
  }

}

<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\ResourcePluginManager.
 */

namespace Drupal\restful\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\plug\Util\Module;

class ResourcePluginManager extends DefaultPluginManager {

  /**
   * Constructs ResourcePluginManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \DrupalCacheInterface $cache_backend
   *   Cache backend instance to use.
   */
  public function __construct(\Traversable $namespaces, \DrupalCacheInterface $cache_backend) {
    parent::__construct('Plugin/resource', $namespaces, 'Drupal\restful\Plugin\resource\ResourceInterface', '\Drupal\restful\Annotation\Resource');
    $this->setCacheBackend($cache_backend, 'resource_plugins');
    $this->alterInfo('resource_plugin');
  }

  /**
   * ResourcePluginManager factory method.
   *
   * @param string $bin
   *   The cache bin for the plugin manager.
   *
   * @return ResourcePluginManager
   *   The created manager.
   */
  public static function create($bin = 'cache') {
    return new static(Module::getNamespaces(), _cache_get_object($bin));
  }

}

<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\FormatterPluginManager.
 */

namespace Drupal\restful\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\plug\Util\Module;

class FormatterPluginManager extends DefaultPluginManager {

  /**
   * Constructs FormatterPluginManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \DrupalCacheInterface $cache_backend
   *   Cache backend instance to use.
   */
  public function __construct(\Traversable $namespaces, \DrupalCacheInterface $cache_backend) {
    parent::__construct('Plugin/formatter', $namespaces, 'Drupal\restful\Plugin\formatter\FormatterInterface', '\Drupal\restful\Annotation\Formatter');
    $this->setCacheBackend($cache_backend, 'formatter_plugins');
    $this->alterInfo('formatter_plugin');
  }

  /**
   * FormatterPluginManager factory method.
   *
   * @param string $bin
   *   The cache bin for the plugin manager.
   *
   * @return FormatterPluginManager
   *   The created manager.
   */
  public static function create($bin = 'cache') {
    return new static(Module::getNamespaces(), _cache_get_object($bin));
  }

}

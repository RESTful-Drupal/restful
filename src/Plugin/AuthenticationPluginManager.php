<?php

/**
 * @file
 * Contains \Drupal\restful\AuthenticationPluginManager.
 */

namespace Drupal\restful;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Factory\ContainerFactory;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;
use Drupal\plug\Util\Module;

/**
 * Name plugin manager.
 */
class AuthenticationPluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  protected $defaults = array(
    // Human readable label for the authentication.
    'label' => '',
    // A description of the plugin.
    'description' => '',
    'settings' => array(),
    // Default class for authentication implementations.
    'class' => 'Drupal\restful\Plugin\authentication\Authentication',
    'id' => '',
  );

  /**
   * Constructs AuthenticationPluginManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \DrupalCacheInterface $cache_backend
   *   Cache backend instance to use.
   */
  public function __construct(\Traversable $namespaces, \DrupalCacheInterface $cache_backend) {
    $this->discovery = new YamlDiscovery('authentication', Module::getDirectories());
    $this->factory = new ContainerFactory($this);
    $this->alterInfo('authentication_plugin');
    $this->setCacheBackend($cache_backend, 'authentication_plugins');
  }

  /**
   * AuthenticationPluginManager factory method.
   *
   * @param string $bin
   *   The cache bin for the plugin manager.
   *
   * @return AuthenticationPluginManager
   *   The created manager.
   */
  public static function create($bin = 'cache') {
    return new static(Module::getNamespaces(), _cache_get_object($bin));
  }

}

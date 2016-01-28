<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\AuthenticationPluginManager.
 */

namespace Drupal\restful\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Factory\ContainerFactory;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;
use Drupal\plug\Util\Module;

/**
 * Authentication plugin manager.
 */
class AuthenticationPluginManager extends DefaultPluginManager {

  use SemiSingletonTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaults = array(
    // Human readable label for the authentication.
    'label' => '',
    // A description of the plugin.
    'description' => '',
    'settings' => array(),
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
    parent::__construct('Plugin/authentication', $namespaces, 'Drupal\restful\Plugin\authentication\AuthenticationInterface', '\Drupal\restful\Annotation\Authentication');
    $this->setCacheBackend($cache_backend, 'authentication_plugins');
    $this->alterInfo('authentication_plugin');
  }

  /**
   * AuthenticationPluginManager factory method.
   *
   * @param string $bin
   *   The cache bin for the plugin manager.
   * @param bool $avoid_singleton
   *   Do not use the stored singleton.
   *
   * @return AuthenticationPluginManager
   *   The created manager.
   */
  public static function create($bin = 'cache', $avoid_singleton = FALSE) {
    $factory = function ($bin) {
      return new static(Module::getNamespaces(), _cache_get_object($bin));
    };
    if ($avoid_singleton) {
      $factory($bin);
    }
    return static::semiSingletonInstance($factory, array($bin));
  }

}

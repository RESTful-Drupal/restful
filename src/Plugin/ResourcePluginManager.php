<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\ResourcePluginManager.
 */

namespace Drupal\restful\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\plug\Util\Module;
use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\ResourceInterface;

class ResourcePluginManager extends DefaultPluginManager {

  /**
   * Request object.
   *
   * @var RequestInterface
   */
  protected $request;

  /**
   * Constructs ResourcePluginManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \DrupalCacheInterface $cache_backend
   *   Cache backend instance to use.
   * @param RequestInterface $request
   *   The request object.
   */
  public function __construct(\Traversable $namespaces, \DrupalCacheInterface $cache_backend, RequestInterface $request) {
    parent::__construct('Plugin/resource', $namespaces, 'Drupal\restful\Plugin\resource\ResourceInterface', '\Drupal\restful\Annotation\Resource');
    $this->setCacheBackend($cache_backend, 'resource_plugins');
    $this->alterInfo('resource_plugin');
    $this->request = $request;
  }

  /**
   * ResourcePluginManager factory method.
   *
   * @param string $bin
   *   The cache bin for the plugin manager.
   * @param RequestInterface $request
   *   The request object.
   *
   * @return ResourcePluginManager
   *   The created manager.
   */
  public static function create($bin = 'cache', RequestInterface $request = NULL) {
    return new static(Module::getNamespaces(), _cache_get_object($bin), $request);
  }

  /**
   * Overrides PluginManagerBase::createInstance().
   *
   * This method is overridden to set the request object when the resource
   * object is instantiated.
   */
  public function createInstance($plugin_id, array $configuration = array()) {
    /* @var ResourceInterface $resource */
    $resource = parent::createInstance($plugin_id, $configuration);
    $resource->setRequest($this->request);
    return $resource;
  }

}

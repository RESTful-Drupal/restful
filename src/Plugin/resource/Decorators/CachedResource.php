<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Decorators\CachedResource
 */

namespace Drupal\restful\Plugin\resource\Decorators;

use Drupal\restful\Http\HttpHeader;
use Drupal\restful\Plugin\resource\DataProvider\CachedDataProvider;
use Drupal\restful\Plugin\resource\DataProvider\CachedDataProviderInterface;
use Drupal\restful\Plugin\resource\DataProvider\DataProviderInterface;
use Drupal\restful\Plugin\resource\ResourceInterface;
use Drupal\restful\Resource\ResourceManager;

class CachedResource extends ResourceDecoratorBase implements CachedResourceInterface {

  /**
   * Cache controller object.
   *
   * @var \DrupalCacheInterface
   */
  protected $cacheController;

  /**
   * The data provider.
   *
   * @var DataProviderInterface
   */
  protected $dataProvider;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param ResourceInterface $subject
   *   The decorated object.
   * @param \DrupalCacheInterface $cache_controller
   *   Injected cache manager.
   */
  public function __construct(ResourceInterface $subject, \DrupalCacheInterface $cache_controller = NULL) {
    // TODO: Implement the ResourceManager factory to use the CachedResource.
    $this->subject = $subject;
    $this->cacheController = $cache_controller ? $cache_controller : $this->newCacheObject();
    $cache_info = $this->defaultCacheInfo();
    $this->pluginDefinition['renderCache'] = $cache_info;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheController() {
    return $this->cacheController;
  }

  /**
   * Get the default cache object based on the plugin configuration.
   *
   * By default, this returns an instance of the DrupalDatabaseCache class.
   * Classes implementing DrupalCacheInterface can register themselves both as a
   * default implementation and for specific bins.
   *
   * @return \DrupalCacheInterface
   *   The cache object associated with the specified bin.
   *
   * @see \DrupalCacheInterface
   * @see _cache_get_object()
   */
  protected function newCacheObject() {
    // We do not use drupal_static() here because we do not want to change the
    // storage of a cache bin mid-request.
    static $cache_object;
    if (isset($cache_object)) {
      // Return cached object.
      return $cache_object;
    }

    $cache_info = $this->defaultCacheInfo();
    $class_name = empty($cache_info['class']) ? NULL : $cache_info['class'];

    // If there is no class name in the plugin definition, try to get it from
    // the variables.
    if (empty($class_name)) {
      $class_name = variable_get('cache_class_' . $cache_info['bin']);
    }
    // If it is still empty, then default to drupal's default cache class.
    if (empty($class_name)) {
      $class_name = variable_get('cache_default_class', 'DrupalDatabaseCache');
    }
    $cache_object = new $class_name($cache_info['bin']);
    return $cache_object;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataProvider() {
    // Make sure that this returns a cached data provider.
    if (isset($this->dataProvider)) {
      return $this->dataProvider;
    }
    $data_provider = $this->dataProviderFactory();
    if ($data_provider instanceof CachedDataProviderInterface) {
      $this->dataProvider = $data_provider;
      return $this->dataProvider;
    }
    $this->dataProvider = new CachedDataProvider($data_provider, $this->getCacheController());
    $plugin_definition = $this->subject->getPluginDefinition();
    $this->dataProvider->addOptions(array(
      'renderCache' => $this->defaultCacheInfo(),
      'resource' => array(
        'version' => array(
          'major' => $plugin_definition['majorVersion'],
          'minor' => $plugin_definition['minorVersion'],
        ),
        'name' => $plugin_definition['resource'],
      ),
    ));
    return $this->dataProvider;
  }

  /**
   * {@inheritdoc}
   */
  public function process() {
    $path = $this->getPath();

    return ResourceManager::executeCallback($this->getControllerFromPath($path), array($path));
  }

  /**
   * {@inheritdoc}
   */
  public function view($path) {
    // TODO: This is duplicating the code from Resource::view
    $ids = explode(static::IDS_SEPARATOR, $path);

    // REST requires a canonical URL for every resource.
    $canonical_path = $this->getDataProvider()->canonicalPath($path);
    $this
      ->getRequest()
      ->getHeaders()
      ->add(HttpHeader::create('Link', $this->versionedUrl($canonical_path, array(), FALSE) . '; rel="canonical"'));

    return $this->getDataProvider()->viewMultiple($ids);
  }

  /**
   * Gets the default cache info.
   *
   * @return array
   *   The cache info.
   */
  protected function defaultCacheInfo() {
    $plugin_definition = $this->subject->getPluginDefinition();
    $cache_info = empty($plugin_definition['renderCache']) ? array() : $plugin_definition['renderCache'];
    $cache_info += array(
      'render' => variable_get('restful_render_cache', FALSE),
      'class' => NULL,
      'bin' => 'cache_restful',
      'expire' => CACHE_PERMANENT,
      'simple_invalidate' => TRUE,
      'granularity' => DRUPAL_CACHE_PER_USER,
    );
    return $cache_info;
  }

  /**
   * {@inheritdoc}
   */
  public function getResourceMachineName() {
    return $this->subject->getResourceMachineName();
  }

}

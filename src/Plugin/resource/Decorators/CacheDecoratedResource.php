<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Decorators\CacheDecoratedResource
 */

namespace Drupal\restful\Plugin\resource\Decorators;

use Drupal\restful\Http\HttpHeader;
use Drupal\restful\Plugin\resource\DataProvider\CacheDecoratedDataProvider;
use Drupal\restful\Plugin\resource\DataProvider\DataProviderInterface;
use Drupal\restful\Plugin\resource\ResourceInterface;
use Drupal\restful\RenderCache\Entity\CacheFragmentController;
use Drupal\restful\RenderCache\RenderCache;
use Drupal\restful\Resource\ResourceManager;

class CacheDecoratedResource extends ResourceDecoratorBase implements CacheDecoratedResourceInterface {

  /**
   * Separator string for cache fragment key value pairs.
   */
  const CACHE_PAIR_SEPARATOR = '#';

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
    // TODO: Implement the ResourceManager factory to use the CacheDecoratedResource.
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
  public function dataProviderFactory() {
    if ($this->dataProvider && $this->dataProvider instanceof CacheDecoratedDataProvider) {
      return $this->dataProvider;
    }
    // Get the data provider from the subject of the decorator.
    $decorated_provider = $this->subject->dataProviderFactory();
    $this->dataProvider = new CacheDecoratedDataProvider($decorated_provider, $this->getCacheController());
    $plugin_definition = $this->getPluginDefinition();
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
  public function getPath() {
    return $this->subject->getPath();
  }

  /**
   * {@inheritdoc}
   */
  public function setPath($path) {
    $this->subject->setPath($path);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDefinitions() {
    return $this->subject->getFieldDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function getDataProvider() {
    if (isset($this->dataProvider)) {
      return $this->dataProvider;
    }
    $this->dataProvider = $this->dataProviderFactory();
    return $this->dataProvider;
  }

  /**
   * {@inheritdoc}
   */
  public function setDataProvider(DataProviderInterface $data_provider = NULL) {
    $this->dataProvider = $data_provider;
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

    // If there is only one ID then use 'view'. Else, use 'viewMultiple'. The
    // difference between the two is that 'view' allows access denied
    // exceptions.
    if (count($ids) == 1) {
      return array($this->getDataProvider()->view($ids[0]));
    }
    else {
      return $this->getDataProvider()->viewMultiple($ids);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function index($path) {
    // TODO: This is duplicating the code from Resource::index
    return $this->getDataProvider()->index();
  }

  /**
   * {@inheritdoc}
   */
  public function update($path) {
    $this->invalidateResourceCache($path);
    // Update according to the decorated.
    return $this->subject->update($path);
  }

  /**
   * {@inheritdoc}
   */
  public function replace($path) {
    $this->invalidateResourceCache($path);
    // Update according to the decorated.
    return $this->subject->replace($path);
  }

  /**
   * {@inheritdoc}
   */
  public function remove($path) {
    $this->invalidateResourceCache($path);
    $this->subject->remove($path);
  }

  /**
   * Gets the default cache info.
   *
   * @return array
   *   The cache info.
   */
  protected function defaultCacheInfo() {
    $plugin_definition = $this->getPluginDefinition();
    $cache_info = empty($plugin_definition['renderCache']) ? array() : $plugin_definition['renderCache'];
    $cache_info += array(
      'render' => variable_get('restful_render_cache', FALSE),
      'class' => NULL,
      'bin' => RenderCache::CACHE_BIN,
      'expire' => CACHE_PERMANENT,
      'simpleInvalidate' => TRUE,
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

  /**
   * {@inheritdoc}
   *
   * This is a decorated resource, get proxy the call until you reach the
   * annotated resource.
   */
  public function getPluginDefinition() {
    return $this->subject->getPluginDefinition();
  }

  /**
   * {@inheritdoc}
   */
  public function enable() {
    $this->subject->enable();
  }

  /**
   * {@inheritdoc}
   */
  public function disable() {
    $this->subject->disable();
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return $this->subject->isEnabled();
  }

  /**
   * {@inheritdoc}
   */
  public function hasSimpleInvalidation() {
    $data_provider = $this->getDataProvider();
    $options = $data_provider->getOptions();
    $cache_info = $options['renderCache'];
    return !empty($cache_info['simpleInvalidate']);
  }

  /**
   * Invalidates the resource cache for the given resource on the provided id.
   *
   * @param string $id
   *   The id.
   */
  protected function invalidateResourceCache($id) {
    // Invalidate the render cache for this resource.
    $query = new \EntityFieldQuery();
    $canonical_id = $this->getDataProvider()->canonicalPath($id);
    $query
      ->entityCondition('entity_type', 'cache_fragment')
      ->propertyCondition('type', 'resource')
      ->propertyCondition('value', $this::serializeKeyValue($this->getResourceName(), $canonical_id));
    foreach (CacheFragmentController::lookUpHashes($query) as $hash) {
      $this->getCacheController()->clear($hash);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function serializeKeyValue($key, $value) {
    return sprintf('%s%s%s', $key, static::CACHE_PAIR_SEPARATOR, $value);
  }

}

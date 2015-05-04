<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\DataProvider\CacheDecoratedDataProvider.
 */

namespace Drupal\restful\Plugin\resource\DataProvider;

use Drupal\restful\Exception\NotImplementedException;
use Drupal\restful\Exception\UnauthorizedException;
use Drupal\restful\Http\Request;
use Drupal\restful\Plugin\resource\Field\ResourceFieldInterface;

class CacheDecoratedDataProvider implements CacheDecoratedDataProviderInterface {

  /**
   * The decorated object.
   *
   * @var DataProviderInterface
   */
  protected $subject;

  /**
   * The cache controller to interact with the cache backed.
   *
   * @var \DrupalCacheInterface
   */
  protected $cacheController;

  /**
   * Constructs a CacheDecoratedDataProvider object.
   *
   * @param DataProviderInterface $subject
   *   The data provider to add caching to.
   * @param \DrupalCacheInterface $cache_controller
   *   The cache controller to add the cache.
   */
  public function __construct(DataProviderInterface $subject, \DrupalCacheInterface $cache_controller) {
    $this->subject = $subject;
    $this->cacheController = $cache_controller;
  }

  /**
   * {@inheritdoc}
   */
  public function getRange() {
    return $this->subject->getRange();
  }

  /**
   * {@inheritdoc}
   */
  public function getAccount() {
    return $this->subject->getAccount();
  }

  /**
   * {@inheritdoc}
   */
  public function getRequest() {
    return $this->subject->getRequest();
  }

  /**
   * {@inheritdoc}
   */
  public function getLangCode() {
    return $this->subject->getLangCode();
  }

  /**
   * {@inheritdoc}
   */
  public function setLangCode($langcode) {
    $this->subject->setLangCode($langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    return $this->subject->getOptions();
  }

  /**
   * {@inheritdoc}
   */
  public function addOptions(array $options) {
    $this->subject->addOptions($options);
  }

  /**
   * {@inheritdoc}
   */
  public function getContext($identifier) {
    return $this->subject->getContext($identifier);
  }

  /**
   * {@inheritdoc}
   */
  public function index() {
    return $this->subject->index();
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return $this->subject->count();
  }

  /**
   * {@inheritdoc}
   */
  public function create($object) {
    return $this->subject->create($object);
  }

  /**
   * {@inheritdoc}
   */
  public function view($identifier) {
    $context = $this->getContext($identifier);
    $cached_data = $this->getRenderedCache($context);
    if (!empty($cached_data->data)) {
      return $cached_data->data;
    }
    $output = $this->subject->view($identifier);

    $this->setRenderedCache($output, $context);
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $identifiers) {
    $context = $this->getContext($identifiers);
    $cached_data = $this->getRenderedCache($context);
    if (!empty($cached_data->data)) {
      return $cached_data->data;
    }
    $output = $this->subject->viewMultiple($identifiers);

    $this->setRenderedCache($output, $context);
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function update($identifier, $object, $replace = TRUE) {
    $this->clearRenderedCache($this->getContext($identifier));
    return $this->subject->update($identifier, $object, $replace);
  }

  /**
   * {@inheritdoc}
   */
  public function remove($identifier) {
    $this->clearRenderedCache($this->getContext($identifier));
    $this->subject->remove($identifier);
  }

  /**
   * {@inheritdoc}
   */
  public function methodAccess(ResourceFieldInterface $resource_field) {
    $this->subject->methodAccess($resource_field);
  }

  /**
   * {@inheritdoc}
   */
  public function canonicalPath($path) {
    return $this->subject->canonicalPath($path);
  }

  /**
   * Get an entry from the rendered cache.
   *
   * @param array $context
   *   An associative array with additional information to build the cache ID.
   *
   * @return object
   *   The cache with rendered entity as returned by DataProviderEntity::view().
   *
   * @see DataProviderEntity::view()
   */
  protected function getRenderedCache(array $context = array()) {
    if (!$this->isCacheEnabled()) {
      return NULL;
    }

    $cid = $this->generateCacheId($context);
    return $this->cacheController->get($cid);
  }

  /**
   * {@inheritdoc}
   */
  public function generateCacheId(array $context = array()) {
    // Get the cache ID from the selected params. We will use a complex cache
    // ID for smarter invalidation. The cache id will be like:
    // v<major version>.<minor version>::uu<user uid>::pa<params array>
    // The code before every bit is a 2 letter representation of the label.
    // For instance, the params array will be something like:
    // fi:id,title::re:admin
    // When the request has ?fields=id,title&restrict=admin
    $options = $this->getOptions();

    // TODO: Pass in the resource information needed here. I know, I know, …
    $version = $options['resource']['version'];
    $account = $this->getAccount();

    $cache_info = $options['renderCache'];
    if ($cache_info['granularity'] == DRUPAL_CACHE_PER_USER) {
      $account_cid = '::uu' . $account->uid;
    }
    elseif ($cache_info['granularity'] == DRUPAL_CACHE_PER_ROLE) {
      // Instead of encoding the user ID in the cache ID add the role ids.
      $account_cid = '::ur' . implode(',', array_keys($account->roles));
    }
    else {
      throw new NotImplementedException(sprintf('The selected cache granularity (%s) is not supported.', $cache_info['granularity']));
    }
    $base_cid = 'v' . $version['major'] . '.' . $version['minor'] . '::' . $options['resource']['name'] . $account_cid . '::pa';

    // Now add the context part to the cid.
    $cid_params = static::addCidParams($context);
    if (Request::isReadMethod($this->getRequest()->getMethod())) {
      // We don't want to split the cache with the body data on write requests.
      $this->getRequest()->clearApplicationData();
      $cid_params = array_merge($cid_params, static::addCidParams($this->getRequest()->getParsedInput()));
    }

    return $base_cid . implode('::', $cid_params);
  }

  /**
   * Delete cached entities from all the cache bins for resources.
   *
   * @param string $cid
   *   The wildcard cache id to invalidate.
   */
  public static function invalidateEntityCache($cid) {
    $plugins = restful()
      ->getResourceManager()
      ->getPlugins();
    $request = restful()->getRequest();
    foreach ($plugins->getIterator() as $instance_id => $plugin) {
      /** @var \Drupal\restful\Plugin\resource\Resource $plugin */
      $plugin->setRequest($request);
      try {
        $data_provider = $plugin->getDataProvider();
      }
      catch (UnauthorizedException $e) {
        // If the user cannot be authorized we don't need to worry about
        // invalidating cache entries, since they won't be there.
        continue;
      }
      if (method_exists($data_provider, 'cacheInvalidate')) {
        $version = $plugin->getVersion();
        // Get the uid for the invalidation.
        try {
          $uid = $plugin->getAccount(FALSE)->uid;
        }
        catch (UnauthorizedException $e) {
          // If no user could be found using the handler default to the logged
          // in user.
          $uid = $GLOBALS['user']->uid;
        }
        $version_cid = 'v' . $version['major'] . '.' . $version['minor'] . '::' . $plugin->getResourceMachineName() . '::uu' . $uid;
        $data_provider->cacheInvalidate($version_cid . '::' . $cid);
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * @see https://api.drupal.org/api/drupal/includes%21cache.inc/function/DrupalCacheInterface%3A%3Aclear/7
   */
  public function cacheInvalidate($cid) {
    $options = $this->getOptions();
    $cache_info = $options['renderCache'];

    if (!$cache_info['simple_invalidate']) {
      // Simple invalidation is disabled. This means it is up to the
      // implementing module to take care of the invalidation.
      return;
    }
    // If the $cid is not '*' then remove the asterisk since it can mess with
    // dynamically built wildcards.
    if ($cid != '*') {
      $pos = strpos($cid, '*');
      if ($pos !== FALSE) {
        $cid = substr($cid, 0, $pos);
      }
    }
    $this->cacheController->clear($cid, TRUE);
  }

  /**
   * Get the cache id parameters based on the keys.
   *
   * @param array $keys
   *   Keys to turn into cache id parameters.
   *
   * @return array
   *   The cache id parameters.
   */
  protected static function addCidParams(array $keys) {
    $cid_params = array();
    foreach ($keys as $param => $value) {
      // Some request parameters don't affect how the resource is rendered, this
      // means that we should skip them for the cache ID generation.
      if (in_array($param, array(
        'filter',
        'loadByFieldName',
        'page',
        'q',
        'range',
        'sort',
      ))) {
        continue;
      }
      // Make sure that ?fields=title,id and ?fields=id,title hit the same cache
      // identifier.
      $values = explode(',', $value);
      sort($values);
      $value = implode(',', $values);

      $cid_params[] = substr($param, 0, 2) . ':' . $value;
    }
    return $cid_params;
  }

  /**
   * Store an entry in the rendered cache.
   *
   * @param mixed $data
   *   The data to be stored into the cache generated by
   *   DataProviderEntity::view().
   * @param array $context
   *   An associative array with additional information to build the cache ID.
   *
   * @return array
   *   The rendered entity as returned by DataProviderEntity::view().
   *
   * @see static::view()
   */
  protected function setRenderedCache($data, array $context = array()) {
    if (!$this->isCacheEnabled()) {
      return;
    }

    $cid = $this->generateCacheId($context);
    $this->cacheController->set($cid, $data, $this->getOptions()['renderCache']['expire']);
  }

  /**
   * Clear an entry from the rendered cache.
   *
   * @param array $context
   *   An associative array with additional information to build the cache ID.
   *
   * @see static::view()
   */
  protected function clearRenderedCache(array $context = array()) {
    if (!$this->isCacheEnabled()) {
      return;
    }

    $cid = $this->generateCacheId($context);
    $this->cacheController->clear($cid);
  }

  /**
   * Helper function that checks if cache is enabled.
   *
   * @return bool
   *   TRUE if the resource has cache enabled.
   */
  protected function isCacheEnabled() {
    $options = $this->getOptions();
    $cache_info = $options['renderCache'];
    return isset($cache_info['render']);
  }

  /**
   * {@inheritdoc}
   */
  public function setOptions(array $options) {
    $this->subject->setOptions($options);
  }

}

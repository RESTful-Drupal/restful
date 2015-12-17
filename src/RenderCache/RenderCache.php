<?php

/**
 * @file
 * Contains \Drupal\restful\RenderCache\RenderCache.
 */

namespace Drupal\restful\RenderCache;

use Doctrine\Common\Collections\ArrayCollection;
use Drupal\restful\RenderCache\Entity\CacheFragmentController;

/**
 * Class RenderCache.
 *
 * @package Drupal\restful\RenderCache
 */
class RenderCache implements RenderCacheInterface {

  /**
   * Stores the default cache bin.
   *
   * @var string
   */
  const CACHE_BIN = 'cache_restful';

  /**
   * The hash for the cache id.
   */
  protected $hash;

  /**
   * The cache fragments.
   *
   * @var ArrayCollection
   */
  protected $cacheFragments;

  /**
   * The cache object.
   *
   * @var \DrupalCacheInterface
   */
  protected $cacheObject;

  /**
   * Create an object of type RenderCache.
   *
   * @param ArrayCollection $cache_fragments
   *   The cache fragments.
   * @param string $hash
   *   The hash for the cache object.
   * @param \DrupalCacheInterface $cache_object
   *   The cache backend to use with this object.
   */
  public function __construct(ArrayCollection $cache_fragments, $hash, \DrupalCacheInterface $cache_object) {
    $this->cacheFragments = $cache_fragments;
    /* @var CacheFragmentController $controller */
    $controller = entity_get_controller('cache_fragment');
    $this->hash = $hash ?: $controller->generateCacheHash($cache_fragments);
    $this->cacheObject = $cache_object;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ArrayCollection $cache_fragments, \DrupalCacheInterface $cache_object) {
    /* @var CacheFragmentController $controller */
    $controller = entity_get_controller('cache_fragment');
    return new static($cache_fragments, $controller->generateCacheHash($cache_fragments), $cache_object);
  }

  /**
   * {@inheritdoc}
   */
  public function get() {
    $cid = $this->generateCacheId();
    $query = new \EntityFieldQuery();
    $count = $query
      ->entityCondition('entity_type', 'cache_fragment')
      ->propertyCondition('hash', $cid)
      ->count()
      ->execute();

    if ($count) {
      return $this->cacheObject->get($cid);
    }
    // If there are no cache fragments for the given hash then clear the cache
    // and return NULL.
    $this->cacheObject->clear($cid);
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function set($value) {
    /* @var CacheFragmentController $controller */
    $controller = entity_get_controller('cache_fragment');
    if (!$controller->createCacheFragments($this->cacheFragments)) {
      return;
    }
    $this->cacheObject->set($this->generateCacheId(), $value);
  }

  /**
   * {@inheritdoc}
   */
  public function clear() {
    // Remove the cache.
    $this->cacheObject->clear($this->generateCacheId());
    // Delete all cache fragments for that hash.
    $query = new \EntityFieldQuery();
    $results = $query
      ->entityCondition('entity_type', 'cache_fragment')
      ->propertyCondition('hash', $this->generateCacheId())
      ->execute();
    if (empty($results['cache_fragment'])) {
      return;
    }
    // Delete the actual entities.
    entity_delete_multiple('cache_fragment', array_keys($results['cache_fragment']));
  }

  /**
   * {@inheritdoc}
   */
  public function getCid() {
    return $this->hash;
  }

  /**
   * Generates the cache id based on the hash and the fragment IDs.
   *
   * @return string
   *   The cid.
   */
  protected function generateCacheId() {
    return $this->hash;
  }

}

<?php

/**
 * @file
 * Contains \Drupal\restful\RenderCache\RenderCache.
 */

namespace Drupal\restful\RenderCache;

use Doctrine\Common\Collections\ArrayCollection;
use Drupal\Component\Plugin\PluginBase;
use Drupal\restful\RenderCache\Entity\CacheFragment;
use Drupal\restful\RenderCache\Entity\CacheFragmentController;

class RenderCache implements RenderCacheInterface {

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
   * Stores the default cache bin.
   *
   * @var string
   */
  protected static $cacheBin = 'cache_restful';

  /**
   * Create an object of type RenderCache.
   *
   * @param string $hash
   *   The hash for the cache object.
   */
  public function __construct(ArrayCollection $cache_fragments, $hash) {
    $this->cacheFragments = $cache_fragments;
    $this->hash = $hash ?: entity_get_controller('cache_fragment')
      ->generateCacheHash($cache_fragments);
    $this->cacheObject = _cache_get_object($this::$cacheBin);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ArrayCollection $cache_fragments) {
    /* @var CacheFragmentController $controller */
    $controller = entity_get_controller('cache_fragment');
    return new static($cache_fragments, $controller->generateCacheHash($cache_fragments));
  }

  /**
   * {@inheritdoc}
   */
  public function get() {
    return $this->cacheObject->get($this->hash);
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
   * Generates the cache id based on the hash and the fragment IDs.
   *
   * @return string
   *   The cid.
   */
  protected function generateCacheId() {
    return $this->hash;
  }

}

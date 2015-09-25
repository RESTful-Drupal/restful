<?php

/**
 * @file
 * Contains \Drupal\restful\RenderCache\RenderCache.
 */

namespace Drupal\restful\RenderCache;

use Doctrine\Common\Collections\ArrayCollection;
use Drupal\Component\Plugin\PluginBase;
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
   * Create an object of type RenderCache.
   *
   * @param string $hash
   *   The hash for the cache object.
   */
  public function __construct(ArrayCollection $cache_fragments, $hash) {
    $this->hash = $hash ?: entity_get_controller('cache_fragment')
      ->generateCacheHash($cache_fragments);
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
    return _cache_get_object('cache_restful')->get($this->hash);
  }

  /**
   * {@inheritdoc}
   */
  public function set($value) {
    /* @var CacheFragmentController $controller */
    $controller = entity_get_controller('cache_fragment');
    $tags = $controller->createCacheFragments($this->cacheFragments);
    $cid = $this->hash . PluginBase::DERIVATIVE_SEPARATOR . implode(',', $tags);
    _cache_get_object('cache_restful')->set($cid, $value);
  }

}

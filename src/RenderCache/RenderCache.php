<?php

/**
 * @file
 * Contains \Drupal\restful\RenderCache\RenderCache.
 */

namespace Drupal\restful\RenderCache;

use Doctrine\Common\Collections\ArrayCollection;
use Drupal\restful\RenderCache\Entity\CacheTagController;

class RenderCache implements RenderCacheInterface {

  /**
   * {@inheritdoc}
   */
  protected $hash;

  /**
   * Create an object of type RenderCache.
   *
   * @param string $hash
   *   The hash for the cache object.
   */
  public function __construct($hash) {
    $this->hash = $hash;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ArrayCollection $cache_tags) {
    /* @var CacheTagController $controller */
    $controller = entity_get_controller('cache_tag');
    $tags = $controller->createCacheTags($cache_tags);
    return new static($controller->generateCacheHash($cache_tags), $tags);
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
    _cache_get_object('cache_restful')->set($this->hash, $value);
  }

}

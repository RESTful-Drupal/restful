<?php

/**
 * @file
 * Contains \Drupal\restful\RenderCache\RenderCacheInterface.
 */

namespace Drupal\restful\RenderCache;

use Doctrine\Common\Collections\ArrayCollection;
use Drupal\restful\RenderCache\Entity\CacheFragmentController;

interface RenderCacheInterface {


  /**
   * Factory function to create a new RenderCacheInterface object.
   *
   * @param ArrayCollection $cache_fragments
   *   The tags collection.
   * @param \DrupalCacheInterface $cache_object
   *   The cache backend to use.
   *
   * @return RenderCacheInterface
   *   The cache controller.
   */
  public static function create(ArrayCollection $cache_fragments, \DrupalCacheInterface $cache_object);

  /**
   * Get the cache.
   *
   * @return mixed
   *   The cache value.
   */
  public function get();

  /**
   * Set the cache.
   *
   * @param mixed $value
   *   The value to cache.
   */
  public function set($value);

  /**
   * Clears the cache for the given cache object.
   */
  public function clear();

  /**
   * Get the cache ID (aka cache hash).
   *
   * @return string
   *   The cache ID.
   */
  public function getCid();

}

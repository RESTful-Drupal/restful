<?php

/**
 * @file
 * Contains \Drupal\restful\RenderCache\RenderCacheInterface.
 */

namespace Drupal\restful\RenderCache;

use Doctrine\Common\Collections\ArrayCollection;

interface RenderCacheInterface {


  /**
   * Factory function to create a new RenderCacheInterface object.
   *
   * @param ArrayCollection $cache_fragments
   *   The tags collection.
   *
   * @return RenderCacheInterface
   *   The cache controller.
   */
  public static function create(ArrayCollection $cache_fragments);

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
}

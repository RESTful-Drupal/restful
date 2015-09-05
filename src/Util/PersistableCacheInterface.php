<?php

/**
 * @file
 * Contains \Drupal\restful\Util\PersistableCacheInterface.
 */

namespace Drupal\restful\Util;

interface PersistableCacheInterface {

  /**
   * Checks if the cache contains the key.
   *
   * @param string $key
   *   The key to check.
   *
   * @return bool
   *   TRUE if the key is present in the cache. FALSE otherwise.
   */
  public function contains($key);

  /**
   * Gets the memory reference of the cached item.
   *
   * @param string $key
   *   The key to get.
   *
   * @return mixed
   *   The reference to the value.
   */
  public function &get($key);

  /**
   * Gets the memory reference of the cached item.
   *
   * @param string $key
   *   The key to set.
   * @param mixed $value
   *   The value to set.
   */
  public function set($key, $value);

  /**
   * Delete a cached item.
   *
   * @param string $key
   *   The key to delete.
   */
  public function delete($key);

  /**
   * Persist the cache to the RESTful cache.
   */
  public function persist();

}

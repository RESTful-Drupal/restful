<?php

/**
 * @file
 * Contains RestfulStaticCacheControllerInterface
 */

interface RestfulStaticCacheControllerInterface {

  /**
   * Gets the static cache.
   *
   * @param string $cid
   *   The cache key to use.
   * @param mixed $default
   *   The default value in case there is no static cache.
   *
   * @return mixed
   *   The cached value.
   */
  public function get($cid, $default = NULL);

  /**
   * Sets the static cache.
   *
   * @param string $cid
   *   The cache key to use.
   * @param mixed $value
   *   The value to set.
   *
   * @return mixed
   *   The cached value.
   */
  public function set($cid, $value);

  /**
   * Clear a particular cache value.
   *
   * @param string $cid
   *   The cache ID to clear.
   */
  public function clear($cid);

  /**
   * Clear all registered cache values.
   */
  public function clearAll();

}

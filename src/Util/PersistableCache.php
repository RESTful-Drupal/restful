<?php

/**
 * @file
 * Contains \Drupal\restful\Util\PersistableCache.
 */

namespace Drupal\restful\Util;

class PersistableCache implements PersistableCacheInterface {

  /**
   * The data array.
   *
   * @var array
   */
  protected $data = array();

  /**
   * Tracks the loaded keys.
   *
   * @var string[]
   */
  protected $loaded = array();

  /**
   * The cache bin where to store the caches.
   *
   * @var string
   */
  protected $cacheBin = 'cache';

  /**
   * PersistableCache constructor.
   *
   * @param string $cache_bin
   *   The cache bin where to store the persisted cache.
   */
  public function __construct($cache_bin = NULL) {
    $this->cacheBin = $cache_bin ?: 'cache';
  }


  /**
   * {@inheritdoc}
   */
  public function contains($key) {
    return isset($this->data[$key]);
  }

  /**
   * {@inheritdoc}
   */
  public function &get($key) {
    if (!$this->contains($key)) {
      // Load from the real cache if it's not loaded yet.
      $this->load($key);
    }
    if (!$this->contains($key)) {
      $this->data[$key] = NULL;
    }
    return $this->data[$key];
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    $this->data[$key] = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function delete($key) {
    unset($this->data[$key]);
  }

  /**
   * {@inheritdoc}
   */
  public function persist() {
    foreach ($this->data as $key => $value) {
      cache_set($key, $value, $this->cacheBin);
    }
  }

  /**
   * Persist the data in the cache backend during shutdown.
   */
  public function __destruct() {
    $this->persist();
  }

  /**
   * Checks if a key was already loaded before.
   *
   * @param string $key
   *   The key to check.
   *
   * @return bool
   *   TRUE if it was loaded before. FALSE otherwise.
   */
  protected function isLoaded($key) {
    return isset($this->loaded[$key]);
  }

  /**
   * Tries to load an item from the real cache.
   *
   * @param string $key
   *   The key of the item.
   */
  protected function load($key) {
    if ($this->isLoaded($key)) {
      return;
    }
    // Mark the key as loaded.
    $this->loaded[$key] = TRUE;
    if ($cache = cache_get($key, $this->cacheBin)) {
      $this->data[$key] = $cache->data;
    }
  }

}

<?php

/**
 * @file
 * Contains RestfulStaticCacheController
 */

class RestfulStaticCacheController implements \RestfulStaticCacheControllerInterface {

  /**
   * @var array
   *
   * The cache ID registry.
   */
  protected $cids = array();

  /**
   * @var string
   *
   * The cache key prefix.
   */
  protected $prefix;

  /**
   * Constructor
   */
  public function __construct() {
    $this->prefix = 'restful_' . mt_rand() . '_';
  }

  /**
   * {@inheritdoc}
   */
  public function get($cid, $default = NULL) {
    $this->registerCid($cid);
    return drupal_static($this->prefix . $cid, $default);
  }

  /**
   * {@inheritdoc}
   */
  public function set($cid, $value) {
    $val = &drupal_static($this->prefix . $cid);
    $val = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function clear($cid) {
    drupal_static_reset($this->prefix . $cid);
  }

  /**
   * {@inheritdoc}
   */
  public function clearAll() {
    foreach ($this->getCids() as $cid) {
      $this->clear($cid);
    }
  }

  /**
   * Register cache ID. The registry is used by clearAll.
   *
   * @param string $cid
   *   The cache ID to register.
   */
  protected function registerCid($cid) {
    if (!in_array($cid, $this->cids)) {
      $this->cids[] = $cid;
    }
  }

  /**
   * Cache ID accessor.
   *
   * @return array
   *   The cache IDs.
   */
  public function getCids() {
    return $this->cids;
  }

}

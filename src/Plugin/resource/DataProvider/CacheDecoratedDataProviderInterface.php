<?php

/**
 * @file
 * Contains Drupal\restful\Plugin\resource\DataProvider\CacheDecoratedDataProviderInterface.
 */

namespace Drupal\restful\Plugin\resource\DataProvider;


interface CacheDecoratedDataProviderInterface extends DataProviderInterface {

  /**
   * Invalidates cache for a certain entity.
   *
   * @param string $cid
   *   The wildcard cache id to invalidate. Do not add * for the wildcard.
   */
  public function cacheInvalidate($cid);

}

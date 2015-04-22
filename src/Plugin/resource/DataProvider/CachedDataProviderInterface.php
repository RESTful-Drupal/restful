<?php

/**
 * @file
 * Contains Drupal\restful\Plugin\resource\DataProvider\CachedDataProviderInterface.
 */

namespace Drupal\restful\Plugin\resource\DataProvider;


interface CachedDataProviderInterface extends DataProviderInterface {

  /**
   * Invalidates cache for a certain entity.
   *
   * @param string $cid
   *   The wildcard cache id to invalidate. Do not add * for the wildcard.
   */
  public function cacheInvalidate($cid);

}

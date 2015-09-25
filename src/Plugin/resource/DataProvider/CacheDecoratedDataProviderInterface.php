<?php

/**
 * @file
 * Contains Drupal\restful\Plugin\resource\DataProvider\CacheDecoratedDataProviderInterface.
 */

namespace Drupal\restful\Plugin\resource\DataProvider;

use Drupal\restful\Exception\NotImplementedException;
use Drupal\restful\Http\Request;
use Drupal\restful\Http\RequestInterface;

interface CacheDecoratedDataProviderInterface extends DataProviderInterface {

  /**
   * Delete cached entities from all the cache bins for resources.
   *
   * @param string $string_context
   *   The wildcard cache id to invalidate.
   */
  public static function invalidateEntityCache($string_context, RequestInterface $request = NULL);

  /**
   * Invalidates cache for a certain entity.
   *
   * @param string $cid
   *   The wildcard cache id to invalidate. Do not add * for the wildcard.
   */
  public function cacheInvalidate($cid);

  /**
   * Generates the cache id based on the provided context.
   *
   * @param array $context
   *   The context array like the one returned from
   *   DataProviderInterface::getCacheFragments()
   * @return string
   *   The cache ID.
   *
   * @see DataProviderInterface::getCacheFragments()
   */
  public function generateCacheId(array $context = array());

}

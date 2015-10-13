<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Decorators\CacheDecoratedResourceInterface.
 */

namespace Drupal\restful\Plugin\resource\Decorators;


interface CacheDecoratedResourceInterface extends ResourceDecoratorInterface {
  /**
   * Generates a serialized key value pair.
   *
   * @param string $key
   *   The key
   * @param string $value
   *   The value.
   *
   * @return string
   *   The serialized value.
   */
  public static function serializeKeyValue($key, $value);


  /**
   * Getter for $cacheController.
   *
   * @return \DrupalCacheInterface
   *   The cache object.
   */
  public function getCacheController();

  /**
   * Checks if simple invalidation is enabled for this resource.
   *
   * @return bool
   *   TRUE if simple invalidation is needed.
   */
  public function hasSimpleInvalidation();

}

<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Decorators\CachedResourceInterface.
 */

namespace Drupal\restful\Plugin\resource\Decorators;


interface CachedResourceInterface extends ResourceDecoratorInterface {


  /**
   * Getter for $cacheController.
   *
   * @return \DrupalCacheInterface
   *   The cache object.
   */
  public function getCacheController();

}

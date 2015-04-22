<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Decorators\ResourceDecoratorInterface.
 */

namespace Drupal\restful\Plugin\resource\Decorators;


use Drupal\restful\Plugin\resource\ResourceInterface;

interface ResourceDecoratorInterface extends ResourceInterface {

  /**
   * Gets the decorated resource.
   *
   * @return ResourceInterface
   *   The underlying resource.
   */
  public function getDecoratedResource();

}

<?php

/**
 * @file
 * Contains \Drupal\restful\Util\ExplorableDecoratorInterface.
 */

namespace Drupal\restful\Util;

/**
 * Class ExplorableDecorator.
 *
 * @package Drupal\restful\Util
 */
interface ExplorableDecoratorInterface {

  /**
   * Checks if the decorated object is an instance of something.
   *
   * @param string $class
   *   Class or interface to check the instance.
   *
   * @return bool
   *   TRUE if the decorated object is an instace of the $class. FALSE
   *   otherwise.
   */
  public function isInstanceOf($class);

}

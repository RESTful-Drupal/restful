<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\SemiSingletonTrait.
 */

namespace Drupal\restful\Plugin;

/**
 * Class SemiSingletonTrait.
 *
 * @package Drupal\restful\Plugin
 */
trait SemiSingletonTrait {

  /**
   * The already created manager.
   *
   * @var mixed
   */
  protected static $singleton;

  /**
   * Gets the singleton based on the factory callable.
   *
   * @param callable $factory
   *   The factory that creates the actual object.
   * @param array $params
   *   The parameters to be passed to the factory.
   *
   * @return mixed
   *   The singleton object.
   */
  protected static function semiSingletonInstance(callable $factory, array $params = array()) {
    if (!static::$singleton) {
      static::$singleton = call_user_func_array($factory, $params);
    }
    return static::$singleton;
  }

}

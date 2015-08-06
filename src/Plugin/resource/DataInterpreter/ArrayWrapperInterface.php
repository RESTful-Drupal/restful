<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\DataInterpreter\ArrayWrapperInterface.
 */

namespace Drupal\restful\Plugin\resource\DataInterpreter;

interface ArrayWrapperInterface {

  /**
   * Gets a field from the data array.
   *
   * @param string $key
   *   The key to get.
   *
   * @return mixed
   *   The value.
   */
  public function get($key);

}

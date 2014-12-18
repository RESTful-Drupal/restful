<?php

/**
 * @file
 * Contains \RestfulPropertySourceInterface.
 */

interface RestfulPropertySourceInterface {

  /**
   * Simple key value getter.
   *
   * @param string $key
   *   The key to get.
   *
   * @return mixed
   *   The value.
   */
  public function get($key);

}

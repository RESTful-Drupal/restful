<?php

/**
 * @file
 * Contains \RestfulPropertySourceInterface.
 */

interface RestfulPropertySourceInterface {

  /**
   * Simple key value getter.
   *
   * @param mixed $key
   *   The key to get.
   *
   * @return mixed
   *   The value.
   */
  public function get($key);

  /**
   * Gets the raw source.
   *
   * @return mixed
   *   The data source.
   */
  public function getSource();

  /**
   * Sets the raw source.
   *
   * @param mixed $source
   *   The data source.
   */
  public function setSource($source);

}

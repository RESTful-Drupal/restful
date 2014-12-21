<?php

/**
 * @file
 * Contains \RestfulPropertySourceArray.
 */

class RestfulPropertySourceArray extends \RestfulPropertySourceBase implements \RestfulPropertySourceInterface {

  /**
   * Constructor.
   *
   * @param array $source
   *   Contains the data object.
   */
  public function __construct(array $source) {
    $this->source = $source;
  }

  /**
   * {@inheritdoc}
   */
  public function get($key, $delta = NULL) {
    return $this->source[$key];
  }

}

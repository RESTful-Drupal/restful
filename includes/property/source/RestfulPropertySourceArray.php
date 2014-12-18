<?php

/**
 * @file
 * Contains \RestfulPropertySourceArray.
 */

class RestfulPropertySourceArray implements \RestfulPropertySourceInterface {

  protected $data;

  /**
   * Constructor.
   *
   * @param array $data
   *   Contains the data object.
   */
  public function __construct(array $data) {
    $this->data = $data;
  }

  /**
   * {@inheritdoc}
   */
  public function get($key) {
    return $this->data[$key];
  }

}

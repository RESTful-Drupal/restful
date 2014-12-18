<?php

/**
 * @file
 * Contains \RestfulPropertySourceObject.
 */

class RestfulPropertySourceObject implements \RestfulPropertySourceInterface {

  protected $data;

  /**
   * Constructor.
   *
   * @param object $data
   *   Contains the data object.
   */
  public function __construct($data) {
    $this->data = $data;
  }

  /**
   * {@inheritdoc}
   */
  public function get($key) {
    return $this->data->{$key};
  }

}

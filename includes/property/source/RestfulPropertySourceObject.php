<?php

/**
 * @file
 * Contains \RestfulPropertySourceObject.
 */

class RestfulPropertySourceObject implements \RestfulPropertySourceInterface {

  protected $source;

  /**
   * Constructor.
   *
   * @param object $source
   *   Contains the data object.
   */
  public function __construct($source) {
    $this->source = $source;
  }

  /**
   * {@inheritdoc}
   */
  public function get($key) {
    return $this->source->{$key};
  }

  /**
   * {@inheritdoc}
   */
  public function getSource() {
    return $this->source;
  }

  /**
   * {@inheritdoc}
   */
  public function setSource($source) {
    return $this->source = $source;
  }

}

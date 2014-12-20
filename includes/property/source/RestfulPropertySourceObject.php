<?php

/**
 * @file
 * Contains \RestfulPropertySourceObject.
 */

class RestfulPropertySourceObject extends \RestfulPropertySourceBase implements \RestfulPropertySourceInterface {

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
  public function get($key, $delta = NULL) {
    return $this->source->{$key};
  }

}

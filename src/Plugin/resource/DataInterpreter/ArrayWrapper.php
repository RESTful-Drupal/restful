<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\DataInterpreter\ArrayWrapper.
 */

namespace Drupal\restful\Plugin\resource\DataInterpreter;

class ArrayWrapper implements ArrayWrapperInterface {

  /**
   * Plugin configuration.
   *
   * @var array
   */
  protected $data = array();

  /**
   * Constructs a PluginWrapper object.
   *
   * @param array $data
   *   The array to wrap
   */
  public function __construct(array $data) {
    $this->data = $data;
  }

  /**
   * {@inheritdoc}
   */
  public function get($key) {
    return isset($this->data[$key]) ? $this->data[$key] : NULL;
  }

}

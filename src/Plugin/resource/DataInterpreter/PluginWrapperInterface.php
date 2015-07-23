<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\DataInterpreter\PluginWrapperInterface.
 */

namespace Drupal\restful\Plugin\resource\DataInterpreter;


interface PluginWrapperInterface {

  /**
   * Gets a field from the plugin configuration.
   *
   * @param string $key
   *   The key to get.
   *
   * @return mixed
   *   The value.
   */
  public function get($key);

}

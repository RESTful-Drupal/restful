<?php

/**
 * @file
 * Contains \Drupal\restful\Resource\ResourceManagerInterface.
 */

namespace Drupal\restful\Resource;

interface ResourceManagerInterface {

  /**
   * Gets the major and minor version for the current request.
   *
   * @return array
   *   The array with the version.
   */
  public function getVersionFromRequest();

  /**
   * Gets the resource plugin based on the information in the request object.
   *
   * @throws \RestfulServerConfigurationException
   *   If the plugin could not be found.
   *
   * @return Resource
   *   The resource plugin instance.
   */
  public function negotiate();

  /**
   * Execute a user callback.
   *
   * @param mixed $callback
   *   There are 3 ways to define a callback:
   *     - String with a function name. Ex: 'drupal_map_assoc'.
   *     - An array containing an object and a method name of that object.
   *       Ex: array($this, 'format').
   *     - An array containing any of the methods before and an array of
   *       parameters to pass to the callback.
   *       Ex: array(array($this, 'processing'), array('param1', 2))
   * @param array $params
   *   Array of additional parameters to pass in.
   *
   * @return mixed
   *   The return value of the callback.
   *
   * @throws \RestfulException
   */
  public static function executeCallback($callback, array $params = array());

}

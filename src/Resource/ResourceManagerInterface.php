<?php

/**
 * @file
 * Contains \Drupal\restful\Resource\ResourceManagerInterface.
 */

namespace Drupal\restful\Resource;

use \Drupal\restful\Exception\RestfulException;
use \Drupal\restful\Exception\ServerConfigurationException;
use Drupal\restful\Http\RequestInterface;
use \Drupal\restful\Plugin\resource\ResourceInterface;
use \Drupal\Component\Plugin\Exception\PluginNotFoundException;

interface ResourceManagerInterface {

  /**
   * Gets the plugin collection for this plugin manager.
   *
   * @param bool $only_enabled
   *   Only get the enabled resources. Defaults to TRUE.
   *
   * @return ResourcePluginCollection
   *   The plugin collection.
   */
  public function getPlugins($only_enabled = TRUE);

  /**
   * Get a resource plugin instance by instance ID.
   *
   * @param string $instance_id
   *   The instance ID.
   * @param RequestInterface $request
   *   The request object.
   *
   * @return ResourceInterface
   *   The plugin.
   *
   * @throws PluginNotFoundException
   *   If the plugin instance cannot be found.
   */
  public function getPlugin($instance_id, RequestInterface $request = NULL);

  /**
   * Clears the static cache version of the plugin.
   *
   * @param string $instance_id
   *   Instance ID of the plugin.
   */
  public function clearPluginCache($instance_id);

  /**
   * Get the resource name for the current request.
   *
   * @return string
   *   The resource ID.
   */
  public function getResourceIdFromRequest();

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
   * @throws ServerConfigurationException
   *   If the plugin could not be found.
   *
   * @return ResourceInterface
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
   * @throws RestfulException
   */
  public static function executeCallback($callback, array $params = array());

  /**
   * Determine if a callback is valid.
   *
   * @param mixed $callback
   *   There are 3 ways to define a callback:
   *     - String with a function name. Ex: 'drupal_map_assoc'.
   *     - An array containing an object and a method name of that object.
   *       Ex: array($this, 'format').
   *     - An array containing any of the methods before and an array of
   *       parameters to pass to the callback.
   *       Ex: array(array($this, 'processing'), array('param1', 2))
   *
   * @return bool
   *   TRUE if the provided callback can be used in static::executeCallback.
   */
  public static function isValidCallback($callback);

  /**
   * Return the last version for a given resource.
   *
   * @param string $resource_name
   *   The name of the resource.
   * @param int $major_version
   *   Get the last version for this major version. If NULL the last major
   *   version for the resource will be used.
   *
   * @return array
   *   Array containing the majorVersion and minorVersion.
   */
  public function getResourceLastVersion($resource_name, $major_version = NULL);

  /**
   * Get the menu item callback.
   *
   * @param string $path
   *   The path to match the router item. Leave it empty to use the current one.
   *
   * @return string
   *   The callback function to be executed.
   */
  public static function getPageCallback($path = NULL);

  /**
   * Get a copy of resource plugin instance by instance ID.
   *
   * This is useful when you have sub-requests, since you don't want to change
   * state to other resources.
   *
   * @param string $instance_id
   *   The instance ID.
   * @param RequestInterface $request
   *   The request object.
   *
   * @return ResourceInterface
   *   The plugin.
   *
   * @throws PluginNotFoundException
   *   If the plugin instance cannot be found.
   */
  public function getPluginCopy($instance_id, RequestInterface $request = NULL);

}

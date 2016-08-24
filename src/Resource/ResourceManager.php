<?php

/**
 * @file
 * Contains \Drupal\restful\Resource\ResourceManager.
 */

namespace Drupal\restful\Resource;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Plugin\PluginBase;
use Drupal\restful\Exception\ServerConfigurationException;
use Drupal\restful\Exception\UnauthorizedException;
use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\ResourceInterface;
use Drupal\restful\Plugin\ResourcePluginManager;

class ResourceManager implements ResourceManagerInterface {

  /**
   * The request object.
   *
   * @var RequestInterface
   */
  protected $request;

  /**
   * The plugin manager.
   *
   * @var ResourcePluginManager
   */
  protected $pluginManager;

  /**
   * The resource plugins.
   *
   * @var ResourcePluginCollection
   */
  protected $plugins;

  /**
   * Constructor for ResourceManager.
   *
   * @param RequestInterface $request
   *   The request object.
   * @param ResourcePluginManager $manager
   *   The plugin manager.
   */
  public function __construct(RequestInterface $request, ResourcePluginManager $manager = NULL) {
    $this->request = $request;
    $this->pluginManager = $manager ?: ResourcePluginManager::create('cache', $request);
    $options = array();
    foreach ($this->pluginManager->getDefinitions() as $plugin_id => $plugin_definition) {
      // Set the instance id to articles::1.5 (for example).
      $options[$plugin_id] = $plugin_definition;
    }
    $this->plugins = new ResourcePluginCollection($this->pluginManager, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugins($only_enabled = TRUE) {
    if (!$only_enabled) {
      return $this->plugins;
    }
    $cloned_plugins = clone $this->plugins;
    $instance_ids = $cloned_plugins->getInstanceIds();
    foreach ($instance_ids as $instance_id) {
      $plugin = NULL;
      try {
        $plugin = $cloned_plugins->get($instance_id);
      }
      catch (UnauthorizedException $e) {}
      if (!$plugin instanceof ResourceInterface) {
        $cloned_plugins->remove($instance_id);
        $cloned_plugins->removeInstanceId($instance_id);
      }
    }
    return $cloned_plugins;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugin($instance_id, RequestInterface $request = NULL) {
    /* @var ResourceInterface $plugin */
    if (!$plugin = $this->plugins->get($instance_id)) {
      return NULL;
    }
    if ($request) {
      $plugin->setRequest($request);
    }
    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCopy($instance_id, RequestInterface $request = NULL) {
    if (!$plugin = $this->pluginManager->createInstance($instance_id)) {
      return NULL;
    }
    if ($request) {
      $plugin->setRequest($request);
    }
    // Allow altering the resource, this way we can read the resource's
    // definition to return a different class that is using composition.
    drupal_alter('restful_resource', $plugin);
    $plugin = $plugin->isEnabled() ? $plugin : NULL;
    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function clearPluginCache($instance_id) {
    $this->plugins->remove($instance_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getResourceIdFromRequest() {
    $resource_name = &drupal_static(__METHOD__);
    if (isset($resource_name)) {
      return $resource_name;
    }
    $path = $this->request->getPath(FALSE);
    list($resource_name,) = static::getPageArguments($path);
    return $resource_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getVersionFromRequest() {
    $version = &drupal_static(__METHOD__);
    if (isset($version)) {
      return $version;
    }
    $path = $this->request->getPath(FALSE);
    list($resource_name, $version) = static::getPageArguments($path);
    if (preg_match('/^v\d+(\.\d+)?$/', $version)) {
      $version = $this->parseVersionString($version, $resource_name);
      return $version;
    }

    // If there is no version in the URL check the header.
    if ($version_string = $this->request->getHeaders()->get('x-api-version')->getValueString()) {
      $version = $this->parseVersionString($version_string, $resource_name);
      return $version;
    }

    // If there is no version negotiation information return the latest version.
    $version = $this->getResourceLastVersion($resource_name);
    return $version;
  }

  /**
   * {@inheritdoc}
   */
  public function negotiate() {
    $version = $this->getVersionFromRequest();
    list($resource_name,) = static::getPageArguments($this->request->getPath(FALSE));
    try {
      $resource = $this->getPlugin($resource_name . PluginBase::DERIVATIVE_SEPARATOR . $version[0] . '.' . $version[1]);
      return $resource->isEnabled() ? $resource : NULL;
    }
    catch (PluginNotFoundException $e) {
      throw new ServerConfigurationException($e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function executeCallback($callback, array $params = array()) {
    if (!is_callable($callback)) {
      if (is_array($callback) && count($callback) == 2 && is_array($callback[1])) {
        // This code deals with the third scenario in the docblock. Get the
        // callback and the parameters from the array, merge the parameters with
        // the existing ones and call recursively to reuse the logic for the
        // other cases.
        return static::executeCallback($callback[0], array_merge($params, $callback[1]));
      }
      $callback_name = is_array($callback) ? $callback[1] : $callback;
      throw new ServerConfigurationException("Callback function: $callback_name does not exist.");
    }

    return call_user_func_array($callback, $params);
  }

  /**
   * {@inheritdoc}
   */
  public static function isValidCallback($callback) {
    // Valid callbacks are:
    //   - 'function_name'
    //   - 'SomeClass::someStaticMethod'
    //   - array('function_name', array('param1', 2))
    //   - array($this, 'methodName')
    //   - array(array($this, 'methodName'), array('param1', 2))
    if (!is_callable($callback)) {
      if (is_array($callback) && count($callback) == 2 && is_array($callback[1])) {
        return static::isValidCallback($callback[0]);
      }
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Get the resource name and version from the page arguments in the router.
   *
   * @param string $path
   *   The path to match the router item. Leave it empty to use the current one.
   *
   * @return array
   *   An array of 2 elements with the page arguments.
   */
  protected static function getPageArguments($path = NULL) {
    $router_item = static::getMenuItem($path);
    $output = array(NULL, NULL);
    if (empty($router_item['page_arguments'])) {
      return $output;
    }
    $page_arguments = $router_item['page_arguments'];

    $index = 0;
    foreach ($page_arguments as $page_argument) {
      $output[$index] = $page_argument;
      $index++;
      if ($index >= 2) {
        break;
      }
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public static function getPageCallback($path = NULL) {
    $router_item = static::getMenuItem($path);
    return isset($router_item['page_callback']) ? $router_item['page_callback'] : NULL;
  }

  /**
   * Parses the version string.
   *
   * @param string $version
   *   The string containing the version information.
   * @param string $resource_name
   *   (optional) Name of the resource to get the latest minor version.
   *
   * @return array
   *   Numeric array with major and minor version.
   */
  protected function parseVersionString($version, $resource_name = NULL) {
    if (preg_match('/^v\d+(\.\d+)?$/', $version)) {
      // Remove the leading 'v'.
      $version = substr($version, 1);
    }
    $output = explode('.', $version);
    if (count($output) == 1) {
      $major_version = $output[0];
      // Abort if the version is not numeric.
      if (!$resource_name || !ctype_digit((string) $major_version)) {
        return NULL;
      }
      // Get the latest version for the resource.
      return $this->getResourceLastVersion($resource_name, $major_version);
    }
    // Abort if any of the versions is not numeric.
    if (!ctype_digit((string) $output[0]) || !ctype_digit((string) $output[1])) {
      return NULL;
    }
    return $output;
  }

  /**
   * Get the non translated menu item.
   *
   * @param string $path
   *   The path to match the router item. Leave it empty to use the current one.
   *
   * @return array
   *   The page arguments.
   *
   * @see menu_get_item()
   */
  protected static function getMenuItem($path = NULL) {
    $router_items = &drupal_static(__METHOD__);
    if (!isset($path)) {
      $path = $_GET['q'];
    }
    if (!isset($router_items[$path])) {
      $original_map = arg(NULL, $path);

      $parts = array_slice($original_map, 0, MENU_MAX_PARTS);
      $ancestors = menu_get_ancestors($parts);
      $router_item = db_query_range('SELECT * FROM {menu_router} WHERE path IN (:ancestors) ORDER BY fit DESC', 0, 1, array(':ancestors' => $ancestors))->fetchAssoc();

      if ($router_item) {
        // Allow modules to alter the router item before it is translated and
        // checked for access.
        drupal_alter('menu_get_item', $router_item, $path, $original_map);

        $router_item['original_map'] = $original_map;
        if ($original_map === FALSE) {
          $router_items[$path] = FALSE;
          return FALSE;
        }
        $router_item['map'] = $original_map;
        $router_item['page_arguments'] = array_merge(menu_unserialize($router_item['page_arguments'], $original_map), array_slice($original_map, $router_item['number_parts']));
        $router_item['theme_arguments'] = array_merge(menu_unserialize($router_item['theme_arguments'], $original_map), array_slice($original_map, $router_item['number_parts']));
      }
      $router_items[$path] = $router_item;
    }
    return $router_items[$path];
  }

  /**
   * {@inheritdoc}
   */
  public function getResourceLastVersion($resource_name, $major_version = NULL) {
    $resources = array();
    // Get all the resources corresponding to the resource name.
    foreach ($this->pluginManager->getDefinitions() as $plugin_id => $plugin_definition) {
      if ($plugin_definition['resource'] != $resource_name || (isset($major_version) && $plugin_definition['majorVersion'] != $major_version)) {
        continue;
      }
      $resources[$plugin_definition['majorVersion']][$plugin_definition['minorVersion']] = $plugin_definition;
    }
    // Sort based on the major version.
    ksort($resources, SORT_NUMERIC);
    // Get a list of resources for the latest major version.
    $resources = end($resources);
    if (empty($resources)) {
      return  NULL;
    }
    // Sort based on the minor version.
    ksort($resources, SORT_NUMERIC);
    // Get the latest resource for the minor version.
    $resource = end($resources);
    return array($resource['majorVersion'], $resource['minorVersion']);
  }

}

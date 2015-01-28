<?php

/**
 * @file
 * Contains \Drupal\restful\Resource\ResourceManager.
 */

namespace Drupal\restful\Resource;

use Drupal\restful\Http\Request;

class ResourceManager implements ResourceManagerInterface {

  /**
   * The request object.
   *
   * @var Request
   */
  protected $request;

  /**
   * {@inheritdoc}
   */
  public function getVersionFromRequest() {
    $path = $this->request->getPath();
    $version = &drupal_static(__CLASS__ . '::' . __FUNCTION__);
    if (isset($version)) {
      return $version;
    }
    list($resource_name, $version) = static::getPageArguments($path);
    if (preg_match('/^v\d+(\.\d+)?$/', $version)) {
      $version = static::parseVersionString($version, $resource_name);
      return $version;
    }

    // If there is no version in the URL check the header.
    if ($api_version_header = $this->request->getHeaders()->get('x-api-version')) {
      $version =  static::parseVersionString($api_version_header->getValueString(), $resource_name);
      return $version;
    }

    // If there is no version negotiation information return the latest version.
    $version = static::getResourceLastVersion($resource_name);
    return $version;
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
  protected static function parseVersionString($version, $resource_name = NULL) {
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
      return static::getResourceLastVersion($resource_name, $major_version);
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
   * @see menu_get_item().
   */
  protected static function getMenuItem($path = NULL) {
    $router_items = &drupal_static(__CLASS__ . '::' . __FUNCTION__);
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
   * Return the last version for a given resource.
   *
   * @param string $resource_name
   *   The name of the resource.
   * @param int $major_version
   *   Get the last version for this major version. If NULL the last major
   *   version for the resource will be used.
   *
   * @return array
   *   Array containing the major_version and minor_version.
   */
  protected static function getResourceLastVersion($resource_name, $major_version = NULL) {
    $resources = array();
    // Get all the resources corresponding to the resource name.
    foreach (restful_get_restful_plugins() as $resource) {
      if ($resource['resource'] != $resource_name || (isset($major_version) && $resource['major_version'] != $major_version)) {
        continue;
      }
      $resources[$resource['major_version']][$resource['minor_version']] = $resource;
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
    return array($resource['major_version'], $resource['minor_version']);
  }

}

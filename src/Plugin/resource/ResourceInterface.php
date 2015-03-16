<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\ResourceInterface.
 */

namespace Drupal\restful\Plugin\resource;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\restful\Exception\NotImplementedException;
use Drupal\restful\Plugin\resource\DataProvider\DataProviderInterface;
use Drupal\restful\Plugin\resource\Field\ResourceFieldCollectionInterface;

interface ResourceInterface extends PluginInspectionInterface, ConfigurablePluginInterface {

  /**
   * Data provider factory.
   *
   * @return DataProviderInterface
   *   The data provider for this resource.
   *
   * @throws NotImplementedException
   */
  public function dataProviderFactory();

  /**
   * Get the user from for request.
   *
   * @param bool $cache
   *   Boolean indicating if the resolved user should be cached for next calls.
   *
   * @return object
   *   The fully loaded user object.
   *
   * @see AuthenticatedResource
   */
  public function getAccount($cache = TRUE);

  /**
   * Get the request object.
   *
   * @return \Drupal\restful\Http\RequestInterface
   *   The request object.
   *
   * @throws \Drupal\restful\Exception\ServerConfigurationException
   */
  public function getRequest();

  /**
   * Gets the path of the resource.
   *
   * The resource path is different from the request path in that it does not
   * contain the RESTful API prefix, the optional version string nor the
   * resource name. All that information is already present in the resource
   * object. The resource path only contains information used to query the data
   * provider.
   *
   * @return string
   *   The resource path.
   */
  public function getPath();

  /**
   * Sets the path of the resource.
   *
   * @param string $path
   *   The path without the RESTful prefix or the version string.
   */
  public function setPath($path);

  /**
   * Gets the field definitions.
   *
   * @return ResourceFieldCollectionInterface
   *   The field definitions
   */
  public function getFieldDefinitions();

  /**
   * Gets the data provider.
   *
   * @return \Drupal\restful\Plugin\resource\DataProvider\DataProviderInterface
   *   The data provider to access the backend storage.
   */
  public function getDataProvider();

  /**
   * Gets the resource name.
   *
   * @returns string
   *   The name of the current resource.
   */
  public function getResourceName();

  /**
   * Controller function that passes the data along and executes right action.
   *
   * @return array
   *   An structured array with the response data.
   *
   * @throws \Drupal\restful\Exception\NotImplementedException
   *   If no controller can be found.
   * @throws \Drupal\restful\Exception\ForbiddenException
   *   If access is denied for the operation.
   */
  public function process();

  /**
   * Gets the controllers.
   *
   * @return array
   *   An structured configuration array. Contains the regular expression for
   *   the path as the key and an array of key values as its value. That array
   *   of key values contains the HTTP method as the key and the name of the
   *   public method to execute as the value. If an access callback is needed
   *   one can be provided by turning the value into an array with the keys:
   *   'callback' and 'access callback'.
   *
   * @see getControllers()
   */
  public function controllersInfo();

  /**
   * Gets the controllers for this resource.
   *
   * @return array
   *   An structured configuration array. Contains the regular expression for
   *   the path as the key and an array of key values as its value. That array
   *   of key values contains the HTTP method as the key and an array with the
   *   callback information as the value.
   *
   *   The callback can be anything that ResourceManager::executeCallback
   *   accepts or a string indicating a public method in the resource plugin
   *   class.
   *
   * @code{
   *   array(
   *     '^.*$' => array(Request::METHOD_GET => array(
   *       'callback' => 'view',
   *       'access callback' => array($this, 'viewAccess'),
   *     )),
   *   );
   * }
   *
   * @todo: Populate the entity controllers so that they have the entity access checks in here.
   */
  public function getControllers();
  /**
   * Basic implementation for listing.
   *
   * @param string $path
   *   The resource path.
   *
   * @return array
   *   An array of structured data for the things being viewed.
   */
  public function index($path);

  /**
   * Basic implementation for view.
   *
   * @param string $path
   *   The resource path.
   *
   * @return array
   *   An array of structured data for the things being viewed.
   */
  public function view($path);

  /**
   * Basic implementation for create.
   *
   * @param string $path
   *   The resource path.
   *
   * @return array
   *   An array of structured data for the thing that was created.
   */
  public function create($path);

  /**
   * Basic implementation for update.
   *
   * @param string $path
   *   The resource path.
   *
   * @return array
   *   An array of structured data for the thing that was updated.
   */
  public function update($path);

  /**
   * Basic implementation for update.
   *
   * @param string $path
   *   The resource path.
   *
   * @return array
   *   An array of structured data for the thing that was replaced.
   */
  public function replace($path);
  /**
   * Basic implementation for update.
   *
   * @param string $path
   *   The resource path.
   */
  public function remove($path);

  /**
   * Return array keyed with the major and minor version of the resource.
   *
   * @return array
   *   Keyed array with the major and minor version as provided in the plugin
   *   definition.
   */
  public function getVersion();

  /**
   * Gets a resource URL based on the current version.
   *
   * @param string $path
   *   The path for the resource
   * @param array $options
   *   Array of options as in url().
   * @param bool $version_string
   *   TRUE to add the version string to the URL. FALSE otherwise.
   *
   * @return string
   *   The fully qualified URL.
   *
   * @see url()
   */
  public function versionedUrl($path = '', $options = array(), $version_string = TRUE);

  /**
   * Determine if user can access the handler.
   *
   * @return bool
   *   TRUE if the current request has access to the requested resource. FALSE
   *   otherwise.
   */
  public function access();

}

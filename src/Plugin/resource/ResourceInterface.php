<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\ResourceInterface.
 */

namespace Drupal\restful\Plugin\resource;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\restful\Exception\BadRequestException;
use Drupal\restful\Exception\ForbiddenException;
use Drupal\restful\Exception\GoneException;
use Drupal\restful\Exception\NotImplementedException;
use Drupal\restful\Exception\ServerConfigurationException;
use Drupal\restful\Http\Request;
use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\DataProvider\DataProviderInterface;
use Drupal\restful\Plugin\resource\Field\ResourceFieldCollection;
use Drupal\restful\Plugin\resource\Field\ResourceFieldCollectionInterface;

/**
 * Interface ResourceInterface.
 *
 * @package Drupal\restful\Plugin\resource
 */
interface ResourceInterface extends PluginInspectionInterface, ConfigurablePluginInterface {

  /**
   * The string that separates multiple ids.
   */
  const IDS_SEPARATOR = ',';

  /**
   * Data provider factory.
   *
   * @return DataProviderInterface
   *   The data provider for this resource.
   *
   * @throws NotImplementedException
   * @throws ServerConfigurationException
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
   * Switches the user back from the original user for the session.
   */
  public function switchUserBack();

  /**
   * {@inheritdoc}
   */
  public function setAccount($account);

  /**
   * Get the request object.
   *
   * @return RequestInterface
   *   The request object.
   *
   * @throws \Drupal\restful\Exception\ServerConfigurationException
   */
  public function getRequest();

  /**
   * Sets the request object.
   *
   * @param RequestInterface $request
   *   The request object.
   */
  public function setRequest(RequestInterface $request);

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
   * Sets the field definitions.
   *
   * @param ResourceFieldCollectionInterface $field_definitions
   *   The field definitions to set.
   */
  public function setFieldDefinitions(ResourceFieldCollectionInterface $field_definitions);

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
   * Gets the resource machine name.
   *
   * @return string
   *   The machine name.
   */
  public function getResourceMachineName();

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
   *     '^.*$' => array(RequestInterface::METHOD_GET => array(
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

  /**
   * Return the controller for a given path.
   *
   * @param string $path
   *   (optional) The path to use. If none is provided the path from the
   *   resource will be used.
   * @param ResourceInterface $resource
   *   (optional) Use the passed in resource instead of $this. This is mainly
   *   used by decorator resources.
   *
   * @return callable
   *   A callable as expected by ResourceManager::executeCallback.
   *
   * @throws BadRequestException
   * @throws ForbiddenException
   * @throws GoneException
   * @throws NotImplementedException
   * @throws ServerConfigurationException
   *
   * @see ResourceManager::executeCallback()
   */
  public function getControllerFromPath($path = NULL, ResourceInterface $resource = NULL);

  /**
   * Enable the resource.
   */
  public function enable();

  /**
   * Disable the resource.
   */
  public function disable();

  /**
   * Checks if the resource is enabled.
   *
   * @return bool
   *   TRUE if the resource plugin is enabled.
   */
  public function isEnabled();

  /**
   * Sets the data provider.
   *
   * @param DataProviderInterface $data_provider
   *   The data provider to set.
   */
  public function setDataProvider(DataProviderInterface $data_provider = NULL);

  /**
   * Sets the plugin definition to the provided array.
   *
   * @param array $plugin_definition
   *   Definition array to set manually.
   */
  public function setPluginDefinition(array $plugin_definition);

  /**
   * Helper method; Get the URL of the resource and query strings.
   *
   * By default the URL is absolute.
   *
   * @param array $options
   *   Array with options passed to url().
   * @param bool $keep_query
   *   If TRUE the $request will be appended to the $options['query']. This is
   *   the typical behavior for $_GET method, however it is not for $_POST.
   *   Defaults to TRUE.
   * @param RequestInterface $request
   *   The request object.
   *
   * @return string
   *   The URL address.
   */
  public function getUrl(array $options = array(), $keep_query = TRUE, RequestInterface $request = NULL);

  /**
   * Discovery controller callback.
   *
   * @param string $path
   *   The requested path.
   *
   * @return array
   *   The resource field collection with the discovery information.
   */
  public function discover($path = NULL);

  /**
   * Shorthand method to perform a quick GET request.
   *
   * @param string $path
   *   The resource path.
   * @param array $query
   *   The parsed query string.
   *
   * @return array
   *   The array ready for the formatter.
   */
  public function doGet($path = '', array $query = array());

  /**
   * Shorthand method to perform a quick POST request.
   *
   * @param array $parsed_body
   *   The parsed body.
   *
   * @return array
   *   The array ready for the formatter.
   */
  public function doPost(array $parsed_body);

  /**
   * Shorthand method to perform a quick PATCH request.
   *
   * @param string $path
   *   The resource path.
   * @param array $parsed_body
   *   The parsed body.
   *
   * @throws \Drupal\restful\Exception\BadRequestException
   *   When the path is not present.
   *
   * @return array
   *   The array ready for the formatter.
   */
  public function doPatch($path, array $parsed_body);

  /**
   * Shorthand method to perform a quick PUT request.
   *
   * @param string $path
   *   The resource path.
   * @param array $parsed_body
   *   The parsed body.
   *
   * @throws \Drupal\restful\Exception\BadRequestException
   *   When the path is not present.
   *
   * @return array
   *   The array ready for the formatter.
   */
  public function doPut($path, array $parsed_body);

  /**
   * Shorthand method to perform a quick DELETE request.
   *
   * @param string $path
   *   The resource path.
   *
   * @throws \Drupal\restful\Exception\BadRequestException
   *   When the path is not present.
   */
  public function doDelete($path);

}

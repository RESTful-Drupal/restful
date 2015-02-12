<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\ResourceInterface.
 */

namespace Drupal\restful\Plugin\resource;

use Drupal\Component\Plugin\PluginInspectionInterface;

interface ResourceInterface extends PluginInspectionInterface {

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
   * Gets the path of the request lazily.
   *
   * @return string
   *   The path without the RESTful prefix or the version string.
   */
  public function getPath();

  /**
   * Gets the field definitions.
   *
   * @return \Drupal\restful\Plugin\resource\Field\ResourceFieldCollectionInterface
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
   * @see getControllers().
   */
  public static function contollersInfo();

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
  public static function getControllers();
  /**
   * Basic implementation for listing.
   *
   * @param $path
   *   The resource path.
   *
   * @return array
   *   An array of structured data for the things being viewed.
   */
  public function index($path);

  /**
   * Basic implementation for view.
   *
   * @param $path
   *   The resource path.
   *
   * @return array
   *   An array of structured data for the things being viewed.
   */
  public function view($path);

  /**
   * Basic implementation for create.
   *
   * @param $path
   *   The resource path.
   *
   * @return array
   *   An array of structured data for the thing that was created.
   */
  public function create($path);

  /**
   * Basic implementation for update.
   *
   * @param $path
   *   The resource path.
   *
   * @return array
   *   An array of structured data for the thing that was updated.
   */
  public function update($path);

  /**
   * Basic implementation for update.
   *
   * @param $path
   *   The resource path.
   *
   * @return array
   *   An array of structured data for the thing that was replaced.
   */
  public function replace($path);
  /**
   * Basic implementation for update.
   *
   * @param $path
   *   The resource path.
   */
  public function remove($path);

}

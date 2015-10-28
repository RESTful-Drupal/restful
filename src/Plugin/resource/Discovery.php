<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Discovery
 */

namespace Drupal\restful\Plugin\resource;

use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\DataInterpreter\DataInterpreterInterface;

/**
 * Class Discovery
 * @package Drupal\restful_example\Plugin\Resource
 *
 * @Resource(
 *   name = "discovery:1.0",
 *   resource = "discovery",
 *   label = "Discovery",
 *   description = "Discovery plugin.",
 *   authenticationTypes = TRUE,
 *   authenticationOptional = TRUE,
 *   discoverable = FALSE,
 *   dataProvider = {
 *     "idField": "name"
 *   },
 *   menuItem = "",
 *   majorVersion = 1,
 *   minorVersion = 0
 * )
 */
class Discovery extends Resource {

  /**
   * {@inheritdoc}
   */
  protected function publicFields() {
    return array(
      'label' => array(
        'property' => 'label',
      ),
      'description' => array(
        'property' => 'description',
      ),
      'name' => array(
        'property' => 'name',
      ),
      'resource' => array(
        'property' => 'resource',
      ),
      'majorVersion' => array(
        'property' => 'majorVersion',
      ),
      'minorVersion' => array(
        'property' => 'minorVersion',
      ),
      'self' => array(
        'callback' => array($this, 'getSelf'),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function dataProviderClassName() {
    return '\Drupal\restful\Plugin\resource\DataProvider\DataProviderPlug';
  }

  /**
   * Returns the URL to the endpoint result.
   *
   * @param DataInterpreterInterface $interpreter
   *   The plugin's data interpreter.
   *
   * @return string
   *   The RESTful endpoint URL.
   */
  public function getSelf(DataInterpreterInterface $interpreter) {
    if ($menu_item = $interpreter->getWrapper()->get('menuItem')) {
      $url = variable_get('restful_hook_menu_base_path', 'api') . '/' . $menu_item;
      return url($url, array('absolute' => TRUE));
    }

    $base_path = variable_get('restful_hook_menu_base_path', 'api');
    return url($base_path . '/v' . $interpreter->getWrapper()->get('majorVersion') . '.' . $interpreter->getWrapper()->get('minorVersion') . '/' . $interpreter->getWrapper()->get('resource'), array('absolute' => TRUE));
  }

  /**
   * @inheritDoc
   */
  public function controllersInfo() {
    return array(
      '' => array(
        // GET returns a list of entities.
        RequestInterface::METHOD_GET => 'index',
        RequestInterface::METHOD_HEAD => 'index',
      ),
      // We don't know what the ID looks like, assume that everything is the ID.
      '^.*$' => array(
        RequestInterface::METHOD_GET => 'view',
        RequestInterface::METHOD_HEAD => 'view',
        RequestInterface::METHOD_PUT => array(
          'callback' => 'replace',
          'access callback' => 'resourceManipulationAccess',
        ),
        RequestInterface::METHOD_DELETE => array(
          'callback' => 'remove',
          'access callback' => 'resourceManipulationAccess',
        ),
      ),
    );
  }

  /**
   * Helper callback to check authorization for write operations.
   *
   * @param string $path
   *   The resource path.
   *
   * @return bool
   *   TRUE to grant access. FALSE otherwise.
   */
  public function resourceManipulationAccess($path) {
    return user_access('administer restful resources', $this->getAccount());
  }

}

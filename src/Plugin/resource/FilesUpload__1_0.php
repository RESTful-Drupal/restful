<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\FilesUpload__1_0
 */

namespace Drupal\restful\Plugin\resource;

use Drupal\restful\Exception\NotImplementedException;
use Drupal\restful\Exception\UnauthorizedException;
use Drupal\restful\Plugin\resource\DataProvider\DataProviderFile;
use Drupal\restful\Plugin\resource\DataProvider\DataProviderInterface;

/**
 * Class FilesUpload__1_0
 * @package Drupal\restful_example\Plugin\Resource
 *
 * @Resource(
 *   name = "files_upload:1.0",
 *   resource = "files_upload",
 *   label = "File upload",
 *   description = "A file upload wrapped with RESTful.",
 *   authenticationTypes = TRUE,
 *   dataProvider = {
 *     "entityType": "file",
 *     "options": {
 *       "scheme": "public"
 *     }
 *   },
 *   majorVersion = 1,
 *   minorVersion = 0
 * )
 */
class FilesUpload__1_0 extends Resource {

  /**
   * Constructs a FilesUpload__1_0 object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    // Set dynamic options that cannot be set in the annotation.
    $plugin_definition = $this->getPluginDefinition();
    $plugin_definition['authenticationOptional'] = variable_get('restful_file_upload_allow_anonymous_user', FALSE);
    $plugin_definition['menuItem'] = variable_get('restful_hook_menu_base_path', 'api') . '/file-upload';

    // Store the plugin definition.
    $this->pluginDefinition = $plugin_definition;
  }

  /**
   * Public fields.
   *
   * @return array
   *   The field definition array.
   */
  protected function publicFields() {
    return array();
  }

  /**
   * Data provider factory.
   *
   * @return DataProviderInterface
   *   The data provider for this resource.
   *
   * @throws NotImplementedException
   */
  public function dataProviderFactory() {
    $plugin_definition = $this->getPluginDefinition();
    $field_definitions = $this->getFieldDefinitions();

    return new DataProviderFile($this->getRequest(), $field_definitions, $this->getAccount(), $plugin_definition['dataProvider']);
  }

  /**
   * {@inheritdoc}
   *
   * If "File entity" module exists, determine access by its provided
   * permissions otherwise, check if variable is set to allow anonymous users to
   * upload. Defaults to authenticated user.
   */
  public function access() {
    // The getAccount method may return an UnauthorizedException when an
    // authenticated user cannot be found. Since this is called from the access
    // callback, not from the page callback we need to catch the exception.
    try {
      $account = $this->getAccount();
    }
    catch (UnauthorizedException $e) {
      // If a user is not found then load the anonymous user to check
      // permissions.
      $account = drupal_anonymous_user();
    }
    if (module_exists('file_entity')) {
      return user_access('bypass file access', $account) || user_access('create files', $account);
    }

    return (variable_get('restful_file_upload_allow_anonymous_user', FALSE) || $account->uid) && parent::access();
  }

}

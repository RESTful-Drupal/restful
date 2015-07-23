<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Discovery
 */

namespace Drupal\restful\Plugin\resource;

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
 *   dataProvider = {
 *     "pluginType": "resource",
 *   },
 *   discoverable = FALSE,
 *   majorVersion = 1,
 *   minorVersion = 0
 * )
 */
class Discovery extends Resource {

  /**
   * Constructs a Discovery object.
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
    $plugin_definition['menuItem'] = variable_get('restful_hook_menu_base_path', 'api');

    // Store the plugin definition.
    $this->pluginDefinition = $plugin_definition;
  }

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
   * @param DataInterpreterInterface $data_interpreter
   *   The plugin's data interpreter.
   *
   * @return string
   *   The RESTful endpoint URL.
   */
  public function getSelf(DataInterpreterInterface $data_interpreter) {
    if ($menu_item = $data_interpreter->getWrapper()->get('menuItem')) {
      return url($menu_item, array('absolute' => TRUE));
    }

    $base_path = variable_get('restful_hook_menu_base_path', 'api');
    return url($base_path . '/v' . $data_interpreter->getWrapper()->get('majorVersion') . '.' . $data_interpreter->getWrapper()->get('minorVersion') . '/' . $data_interpreter->getWrapper()->get('resource'), array('absolute' => TRUE));
  }

}

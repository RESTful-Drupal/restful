<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\CsrfToken.
 */

namespace Drupal\restful\Plugin\resource;
use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Resource\ResourceManager;

/**
 * Class CsrfToken
 * @package Drupal\restful\Plugin\resource
 *
 * @Resource(
 *   name = "csrf_token:1.0",
 *   resource = "csrf_token",
 *   label = "CSRF Token",
 *   description = "Resource that provides CSRF Tokens when using cookie authentication.",
 *   authenticationTypes = TRUE,
 *   authenticationOptional = FALSE,
 *   formatter = "single_json",
 *   renderCache = {
 *     "render": FALSE
 *   },
 *   majorVersion = 1,
 *   minorVersion = 0
 * )
 */
class CsrfToken extends Resource implements ResourceInterface {

  /**
   * Constructs a CsrfToken object.
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
    $plugin_definition['menuItem'] = variable_get('restful_hook_menu_base_path', 'api') . '/session/token';

    // Store the plugin definition.
    $this->pluginDefinition = $plugin_definition;
  }

  /**
   * {@inheritdoc}
   */
  protected function publicFields() {
    return array(
      'X-CSRF-Token' => array(
        'callback' => '\Drupal\restful\Plugin\resource\CsrfToken::getCsrfToken',
      ),
    );
  }

  /**
   * Value callback; Return the CSRF token.
   *
   * @return string
   *   The token.
   */
  public static function getCsrfToken() {
    return drupal_get_token(\Drupal\restful\Plugin\authentication\Authentication::TOKEN_VALUE);
  }

  /**
   * {@inheritdoc}
   */
  public function index($path) {
    $values = array();
    foreach ($this->publicFields() as $public_property => $info) {
      $value = NULL;

      if ($info['callback']) {
        $value = ResourceManager::executeCallback($info['callback']);
      }

      if ($value && !empty($info['process_callbacks'])) {
        foreach ($info['process_callbacks'] as $process_callback) {
          $value = ResourceManager::executeCallback($process_callback, array($value));
        }
      }

      $values[$public_property] = $value;
    }

    return $values;
  }

}

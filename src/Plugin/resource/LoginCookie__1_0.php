<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\LoginCookie__1_0.
 */

namespace Drupal\restful\Plugin\resource;

use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Resource\ResourceManager;

/**
 * Class LoginCookie__1_0
 * @package Drupal\restful\Plugin\resource
 *
 * @Resource(
 *   name = "login_cookie:1.0",
 *   resource = "login_cookie",
 *   label = "Login",
 *   description = "Login a user and return a JSON along with the authentication cookie.",
 *   authenticationTypes = {
 *     "basic_auth"
 *   },
 *   dataProvider = {
 *     "entityType": "user",
 *     "bundles": {
 *       "user"
 *     },
 *   },
 *   majorVersion = 1,
 *   minorVersion = 0
 * )
 */
class LoginCookie__1_0 extends ResourceEntity implements ResourceInterface {

  /**
   * Constructs a LoginCookie__1_0 object.
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
    $plugin_definition['menuItem'] = variable_get('restful_hook_menu_base_path', 'api') . '/login';

    // Store the plugin definition.
    $this->pluginDefinition = $plugin_definition;
  }

  /**
   * {@inheritdoc}
   */
  public function publicFields() {
    $public_fields = parent::publicFields();
    $public_fields['id']['methods'] = array();

    // Just return the hidden ID.
    return array('id' => $public_fields['id']);
  }

  /**
   * Overrides \RestfulBase::controllersInfo().
   */
  public function controllersInfo() {
    return array(
      '' => array(
        RequestInterface::METHOD_GET => 'loginAndRespondWithCookie',
      ),
    );
  }

  /**
   * Login a user and return a JSON along with the authentication cookie.
   *
   * @return array
   *   Array with the public fields populated.
   */
  public function loginAndRespondWithCookie() {
    // Login the user.
    $account = $this->getAccount();
    $this->loginUser($account);

    $user_resource = restful()
      ->getResourceManager()
      ->getPlugin('users:1.0');

    // User resource may be disabled.
    $output = $user_resource ? $user_resource->view($account->uid) : array();
    $output += restful_csrf_session_token();
    return $output;
  }

  /**
   * Log the user in.
   *
   * @param object $account
   *   The user object that was retrieved by the AuthenticationManager.
   */
  public function loginUser($account) {
    global $user;
    // Override the global user.
    $user = user_load($account->uid);

    $login_array = array('name' => $account->name);
    user_login_finalize($login_array);
  }

}

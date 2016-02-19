<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\LoginCookie__1_0.
 */

namespace Drupal\restful\Plugin\resource;

use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\Field\ResourceField;

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
 *   menuItem = "login",
 *   majorVersion = 1,
 *   minorVersion = 0
 * )
 */
class LoginCookie__1_0 extends ResourceEntity implements ResourceInterface {

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
    if ($resource_field_collection = reset($output)) {
      /* @var $resource_field_collection \Drupal\restful\Plugin\resource\Field\ResourceFieldCollectionInterface */
      $resource_field_collection->set('X-CSRF-Token', ResourceField::create(array(
        'public_name' => 'X-CSRF-Token',
        'callback' => '\Drupal\restful\Plugin\resource\LoginCookie__1_0::getCSRFTokenValue',
      )));
    }
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

    $this->authenticationManager->switchUserBack();
    // Explicitly allow a session to be saved, as it was disabled in
    // UserSessionState::switchUser. However this resource is a special one, in
    // the sense that we want to keep the user authenticated after login.
    drupal_save_session(TRUE);

    // Override the global user.
    $user = user_load($account->uid);

    $login_array = array('name' => $account->name);
    user_login_finalize($login_array);
  }

  /**
   * Get the CSRF token string.
   *
   * @return string
   *   The token.
   */
  public static function getCSRFTokenValue() {
    $token = array_values(restful_csrf_session_token());
    return reset($token);
  }

  /**
   * {@inheritdoc}
   */
  public function switchUserBack() {
    // We don't want to switch back in this case!
    drupal_save_session(TRUE);
  }

}

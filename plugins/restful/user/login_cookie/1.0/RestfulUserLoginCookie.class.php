<?php

/**
 * @file
 * Contains RestfulUserLoginCookie.
 */

class RestfulUserLoginCookie extends \RestfulEntityBase {

  /**
   * Overrides \RestfulBase::controllersInfo().
   */
  public static function controllersInfo() {
    return array(
      '' => array(
        \RestfulInterface::GET => 'loginAndRespondWithCookie',
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
    $this->loginUser();

    $version = $this->getVersion();
    $handler = restful_get_restful_handler('users', $version['major'], $version['minor']);

    $output = $handler ? $handler->viewEntity($account->uid) : array();
    $output += restful_csrf_session_token();
    return $output;
  }

  /**
   * Log the user.
   */
  protected function loginUser() {
    global $user;

    $account = $this->getAccount();

    // Explicitly allow a session to be saved, as it was disabled in
    // \RestfulAuthenticationManager::getAccount. However this resource is a
    // special one, in the sense that we want to keep the user authenticated
    // after login.
    drupal_save_session(TRUE);

    // Override the global user.
    $user = user_load($account->uid);

    $login_array = array ('name' => $account->name);
    user_login_finalize($login_array);
  }
}

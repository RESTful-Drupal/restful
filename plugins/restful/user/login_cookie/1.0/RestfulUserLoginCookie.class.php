<?php

/**
 * @file
 * Contains RestfulUserLoginCookie.
 */

class RestfulUserLoginCookie extends \RestfulEntityBase {

  /**
   * Overrides RestfulEntityBase::getPublicFields().
   */
  public function getPublicFields() {
    $public_fields = parent::getPublicFields();
    $public_fields['id'] = array(
      'property' => 'uid',
    );

    $public_fields['name'] = array(
      'property' => 'name',
    );

    return $public_fields;
  }

  /**
   * Overrides \RestfulEntityBase::controllers
   *
   * @var array
   */
  protected $controllers = array(
    '' => array(
      'get' => 'loginAndRespondWithCookie',
    ),
  );

  /**
   * Login a user and return a JSON along with the authentication cookie.
   */
  public function loginAndRespondWithCookie($request = NULL, stdClass $account = NULL) {
    // Login the user.
    $this->loginUser($account);

    $version = $this->getVersion();
    $handler = restful_get_restful_handler('users', $version['major'], $version['minor']);

    return $handler->viewEntity($account->uid, $request, $account);
  }

  /**
   * Log the user.
   *
   * @param $account
   *   The user object that was retrieved by the \RestfulAuthenticationManager.
   */
  public function loginUser($account) {
    global $user;
    // Override the global user.
    $user = user_load($account->uid);

    $login_array = array ('name' => $account->name);
    user_login_finalize($login_array);
  }
}

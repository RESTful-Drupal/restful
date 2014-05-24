<?php
/**
 * @file
 * Contains RestfulAuthenticationCookie.
 */

class RestfulAuthenticationCookie extends RestfulAuthenticationBase implements RestfulAuthenticationInterface {

  /**
   * Implements RestfulAuthenticationInterface::authenticate().
   */
  public function authenticate() {
    if (!drupal_session_started() && !drupal_is_cli()) {
      return NULL;
    }
    global $user;
    return user_load($user->uid);
  }

}

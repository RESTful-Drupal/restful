<?php
/**
 * @file
 * Contains RestfulAuthenticationCookie
 */

class RestfulAuthenticationCookie extends RestfulAuthenticationBase implements RestfulAuthenticationInterface {

  /**
   * Implements RestfulAuthenticationInterface::authenticate().
   */
  public function authenticate() {
    global $user;
    if (drupal_session_started()) {
      return $user;
    }

    return NULL;
  }

}

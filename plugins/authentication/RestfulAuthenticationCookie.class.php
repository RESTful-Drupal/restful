<?php
/**
 * @file
 * Contains RestfulAuthenticationCookie.
 */

class RestfulAuthenticationCookie extends RestfulAuthenticationBase implements RestfulAuthenticationInterface {

  /**
   * Implements RestfulAuthenticationInterface::authenticate().
   */
  public function authenticate($request = NULL) {
    if (!drupal_session_started() && !$this->isCli()) {
      return;
    }
    global $user;
    return user_load($user->uid);
  }

  /**
   * Detects whether the script is running from a command line environment.
   *
   * @return bool
   *   TRUE if a command line environment is detected. FALSE otherwise.
   */
  protected function isCli() {
    // Needed to detect if run-tests.sh is running the tests.
    $cli = isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] == 'Drupal command line';
    return $cli || drupal_is_cli();
  }

}

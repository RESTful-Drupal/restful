<?php
/**
 * @file
 * Contains RestfulAuthenticationCookie.
 */

class RestfulAuthenticationCookie extends RestfulAuthenticationBase implements RestfulAuthenticationInterface {

  /**
   * Implements RestfulAuthenticationInterface::authenticate().
   */
  public function authenticate(array $request = array(), $method = \RestfulInterface::GET) {
    if (!drupal_session_started() && !$this->isCli()) {
      return;
    }

    global $user;
    $account = user_load($user->uid);

    if (!\RestfulBase::isWriteMethod($method) || empty($request['__application']['rest_call'])) {
      // Request is done via API not CURL, or not a write operation, so we don't
      // need to check for a CSRF token.
      return $account;
    }

    if (empty($request['__application']['csrf_token'])) {
      throw new \RestfulBadRequestException('No CSRF token passed in the HTTP header.');
    }

    if (!drupal_valid_token($request['__application']['csrf_token'], \RestfulBase::TOKEN_VALUE)) {
      throw new \RestfulForbiddenException('CSRF token validation failed.');
    }

    // CSRF validation passed.
    return $account;
  }

  /**
   * Detects whether the script is running from a command line environment.
   *
   * @return bool
   *   TRUE if a command line environment is detected. FALSE otherwise.
   */
  protected function isCli() {
    // Needed to detect if run-tests.sh is running the tests.
    $cli = \RestfulManager::getRequestHttpHeader('User-Agent') == 'Drupal command line';
    return $cli || drupal_is_cli();
  }

}

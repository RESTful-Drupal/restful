<?php

/**
 * @file
 * Contains RestfulAuthenticationBasic
 */

class RestfulAuthenticationBasic extends RestfulAuthenticationBase implements RestfulAuthenticationInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(array $request = array(), $method = \RestfulInterface::GET) {
    if (variable_get('restful_skip_basic_auth', FALSE)) {
      // Skip basic auth. The variable may be set if .htaccess password is set
      // on the server.
      return;
    }
    list($username, $password) = $this->getCredentials();
    return isset($username) && isset($password);
  }

  /**
   * {@inheritdoc}
   *
   * @see user_login_authenticate_validate().
   */
  public function authenticate(array $request = array(), $method = \RestfulInterface::GET) {
    list($username, $password) = $this->getCredentials();

    // Do not allow any login from the current user's IP if the limit has been
    // reached. Default is 50 failed attempts allowed in one hour. This is
    // independent of the per-user limit to catch attempts from one IP to log
    // in to many different user accounts.  We have a reasonably high limit
    // since there may be only one apparent IP for all users at an institution.
    if (!flood_is_allowed('failed_login_attempt_ip', variable_get('user_failed_login_ip_limit', 50), variable_get('user_failed_login_ip_window', 3600))) {
      throw new RestfulFloodException(format_string('Rejected by ip flood control.'));
    }
    if (!$uid = db_query_range("SELECT uid FROM {users} WHERE name = :name AND status = 1", 0, 1, array(':name' => $username))->fetchField()) {
      // Always register an IP-based failed login event.
      flood_register_event('failed_login_attempt_ip', variable_get('user_failed_login_ip_window', 3600), ip_address());
      return;
    }
    if (variable_get('user_failed_login_identifier_uid_only', FALSE)) {
      // Register flood events based on the uid only, so they apply for any
      // IP address. This is the most secure option.
      $identifier = $uid;
    }
    else {
      // The default identifier is a combination of uid and IP address. This
      // is less secure but more resistant to denial-of-service attacks that
      // could lock out all users with public user names.
      $identifier = $uid . '-' . ip_address();
    }

    // Don't allow login if the limit for this user has been reached.
    // Default is to allow 5 failed attempts every 6 hours.
    if (flood_is_allowed('failed_login_attempt_user', variable_get('user_failed_login_user_limit', 5), variable_get('user_failed_login_user_window', 21600), $identifier)) {
      // We are not limited by flood control, so try to authenticate.
      if ($uid = user_authenticate($username, $password)) {
        // Clear the user based flood control.
        flood_clear_event('failed_login_attempt_user', $identifier);

        return user_load($uid);
      }
      flood_register_event('failed_login_attempt_user', variable_get('user_failed_login_user_window', 3600), $identifier);
    }
    else {
      flood_register_event('failed_login_attempt_user', variable_get('user_failed_login_user_window', 3600), $identifier);
      throw new RestfulFloodException(format_string('Rejected by user flood control.'));
    }
  }

  /**
   * Get the credentials based on the $_SERVER variables.
   *
   * @return array
   *   A numeric array with the username and password.
   */
  protected function getCredentials() {
    $username = empty($_SERVER['PHP_AUTH_USER']) ? NULL : $_SERVER['PHP_AUTH_USER'];
    $password = empty($_SERVER['PHP_AUTH_PW']) ? NULL : $_SERVER['PHP_AUTH_PW'];

    // Try to fill PHP_AUTH_USER & PHP_AUTH_PW with REDIRECT_HTTP_AUTHORIZATION
    // for compatibility with Apache PHP CGI/FastCGI.
    // This requires the following line in your ".htaccess"-File:
    // RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
    if (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) && !isset($username) && !isset($password)) {
      $authentication = base64_decode(substr($_SERVER['REDIRECT_HTTP_AUTHORIZATION'], 6));
      list($username, $password) = explode(':', $authentication);
      $_SERVER['PHP_AUTH_USER'] = $username;
      $_SERVER['PHP_AUTH_PW'] = $password;
    }

    return array($username, $password);
  }
}

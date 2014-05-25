<?php

/**
 * @file
 * Contains RestfulAuthenticationInterface.
 */

interface RestfulAuthenticationInterface {

  /**
   * Authenticate the request by trying to match a user.
   *
   * @return \stdClass|null
   *   The user object.
   */
  public function authenticate();

  /**
   * Determines if the request can be checked for authentication. For example,
   * when authenticating with HTTP header, return FALSE if the header values do
   * not exist.
   *
   * @return bool
   *   TRUE if the request can be checked for authentication, FALSE otherwise.
   */
  public function applies();

  /**
   * Get the name of the authentication plugin.
   *
   * @return string
   *   The name.
   */
  public function getName();

}

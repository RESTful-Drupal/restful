<?php

/**
 * @file
 * Contains RestfulAuthenticationInterface.
 */

interface RestfulAuthenticationInterface {

  /**
   * Authenticate the request by trying to match a user.
   *
   * @param array $request
   *   The request.
   * @param string $method
   *   The HTTP method. Defaults to "get".
   *
   * @return \stdClass|null
   *   The user object.
   */
  public function authenticate($request = NULL, $method = \RestfulInterface::GET);

  /**
   * Determines if the request can be checked for authentication. For example,
   * when authenticating with HTTP header, return FALSE if the header values do
   * not exist.
   *
   * @param $request
   *   The request.
   * @param string $method
   *   The HTTP method. Defaults to "get".
   *
   * @return bool
   *   TRUE if the request can be checked for authentication, FALSE otherwise.
   */
  public function applies($request = NULL, $method = \RestfulInterface::GET);

  /**
   * Get the name of the authentication plugin.
   *
   * @return string
   *   The name.
   */
  public function getName();

}

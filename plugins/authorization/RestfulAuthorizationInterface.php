<?php

/**
 * @file
 * Interface RestfulAuthorizationInterface
 */

interface RestfulAuthorizationInterface {
  /**
   * Check authorization for the request.
   *
   * @return bool
   *   TRUE if the request has granted access. FALSE otherwise.
   */
  public function authorize();

  /**
   * Authenticate the request by trying to match a user.
   *
   * @return stdClass
   *   The user object.
   */
  public function authenticate();

  /**
   * Get the name of the authorization plugin.
   *
   * @return string
   *   The name.
   */
  public function getName();

}

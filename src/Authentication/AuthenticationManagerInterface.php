<?php

/**
 * @file
 * Contains \Drupal\restful\Authentication\AuthenticationManagerInterface
 */

namespace Drupal\restful\Authentication;

use Drupal\restful\Plugin\authentication\AuthenticationInterface;

interface AuthenticationManagerInterface {

  /**
   * Set the authentications' "optional" flag.
   *
   * @param boolean $is_optional
   *   Determines if the authentication is optional.
   */
  public function setIsOptional($is_optional);

  /**
   * Get the authentications' "optional" flag.
   *
   * @return boolean
   *   TRUE if the authentication is optional.
   */
  public function getIsOptional();

  /**
   * Adds the auth provider to the list.
   *
   * @param string $plugin_id
   *   The authentication plugin id.
   */
  public function addAuthenticationProvider($plugin_id);

  /**
   * Adds all the auth providers to the list.
   */
  public function addAllAuthenticationProviders();

  /**
   * Get the user account for the request.
   *
   * @param array $request
   *   The request.
   * @param string $method
   *   The HTTP method.
   * @param boolean $cache
   *   Boolean indicating if the resolved user should be cached for next calls.
   *
   * @throws \RestfulUnauthorizedException
   * @return \stdClass
   *   The user object.
   */
  public function getAccount(array $request = array(), $method = \RestfulInterface::GET, $cache = TRUE);

  /**
   * Setter method for the account property.
   *
   * @param object $account
   *   The account to set.
   */
  public function setAccount($account);

}

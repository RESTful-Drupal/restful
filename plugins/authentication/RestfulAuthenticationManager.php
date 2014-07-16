<?php

/**
 * @file
 * Contains RestfulAuthenticationManager.
 */

class RestfulAuthenticationManager extends \ArrayObject {

  /**
   * The resolved user object.
   *
   * @var \stdClass
   */
  protected $account;


  /**
   * Determines if authentication is optional.
   *
   * If FALSE, then \RestfulUnauthorizedException is thrown if no authentication
   * was found. Defaults to FALSE.
   *
   * @var bool
   */
  protected $isOptional = FALSE;

  /**
   * Set the authentications' "optional" flag.
   *
   * @param boolean $is_optional
   *   Determines if the authentication is optional.
   */
  public function setIsOptional($is_optional) {
    $this->isOptional = $is_optional;
  }

  /**
   * Get the authentications' "optional" flag.
   *
   * @return boolean
   *   TRUE if the authentication is optional.
   */
  public function getIsOptional() {
    return $this->isOptional;
  }

  /**
   * Adds the auth provider to the list.
   *
   * @param \RestfulAuthenticationInterface $provider
   *   The authentication plugin object.
   */
  public function addAuthenticationProvider(RestfulAuthenticationInterface $provider) {
    $this->offsetSet($provider->getName(), $provider);
  }

  /**
   * Get the user account for the request.
   *
   * @param array $request
   *   The request.
   * @param string $method
   *   The HTTP method.
   *
   * @return \stdClass
   *   The user object.
   */
  public function getAccount(array $request = array(), $method = \RestfulBase::GET) {
    global $user;
    // Return the previously resolved user, if any.
    if (!empty($this->account)) {
      return $this->account;
    }
    // Resolve the user based on the providers in the manager.
    $account = NULL;
    foreach ($this as $provider) {
      if ($provider->applies($request, $method) && $account = $provider->authenticate($request, $method)) {
        // The account has been loaded, we can stop looking.
        break;
      }
    }

    if (!$account) {

      if ($this->count() && !$this->getIsOptional()) {
        // User didn't authenticate against any provider, so we throw an error.
        throw new \RestfulUnauthorizedException('Bad credentials');
      }

      // If the account could not be authenticated default to the global user.
      // Most of the cases the cookie provider will do this for us.
      $account = drupal_anonymous_user();

      if (empty($request['__application']['rest_call'])) {
        // If we are using the API from within Drupal and we have not tried to
        // authenticate using the 'cookie' provider, then we expect to be logged
        // in using the cookie authentication as a last resort.
        $account = $user->uid ? user_load($user->uid) : $account;
      }
    }
    $this->setAccount($account);
    return $account;
  }

  /**
   * Setter method for the account property.
   *
   * @param \stdClass $account
   *   The account to set.
   */
  public function setAccount(\stdClass $account) {
    $this->account = $account;
  }

}

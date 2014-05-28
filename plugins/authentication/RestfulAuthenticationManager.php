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
   * @param $request
   *   The request.
   *
   * @return \stdClass
   *   The user object.
   */
  public function getAccount($request = NULL) {
    global $user;
    // Return the previously resolved user, if any.
    if (!empty($this->account)) {
      return $this->account;
    }
    // Resolve the user based on the providers in the manager.
    $account = NULL;
    foreach ($this as $provider) {
      if ($provider->applies($request) && $account = $provider->authenticate($request)) {
        // The account has been loaded, we can stop looking.
        break;
      }
    }

    if (!$account) {

      if ($this->count()) {
        // User didn't authenticate against any provider, so we throw an error.
        throw new \RestfulUnauthorizedException('Bad credentials');
      }

      // If the account could not be authenticated default to the global user.
      // Most of the cases the cookie provider will do this for us.
      $account = drupal_anonymous_user();

      if (empty($request['rest_call'])) {
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

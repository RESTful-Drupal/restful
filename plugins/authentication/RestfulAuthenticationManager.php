<?php

/**
 * @file
 * Contains RestfulAuthenticationManager
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
   * @return \stdClass
   *   The user object.
   */
  public function getAccount() {
    // Return the previously resolved user, if any.
    if (!empty($this->account)) {
      return $this->account;
    }
    else {
      // Resolve the user based on the providers in the manager.
      $account = NULL;
      foreach ($this as $provider) {
        if ($provider->applies() && $account = $provider->authenticate()) {
          // The account has been loaded, we can stop looking.
          break;
        }
      }

      if (empty($account)) {
        // If the account could not be authenticated default to the anonymous user.
        // Most of the cases the cookie provider will do this for us.
        $account = drupal_anonymous_user();
      }
      $this->setAccount($account);
      return $account;
    }
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

  /**
   * Get the array of authorization provider names. The plugin names.
   *
   * @return array
   *   Array of provider names.
   */
  public function providerNames() {
    return array_keys($this->getArrayCopy());
  }

}

<?php

/**
 * Contains RestfulAuthenticationManager
 */

class RestfulAuthenticationManager extends \ArrayObject {

  /**
   * Adds the auth provider to the list.
   *
   * @param RestfulAuthenticationInterface $provider
   *   The authentication plugin object.
   */
  public function setAuthNProvider(RestfulAuthenticationInterface $provider) {
    $this->offsetSet($provider->getName(), $provider);
  }

  /**
   * Get the user account for the request.
   *
   * @return stdClass
   *   The user object.
   */
  public function getAccount() {
    static $account;
    if (empty($account)) {
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
    }
    return $account;
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

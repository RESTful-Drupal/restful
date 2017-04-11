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
   * The original user object and session.
   *
   * @var array
   */
  protected $originalUserSession;


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
   * @param boolean $cache
   *   Boolean indicating if the resolved user should be cached for next calls.
   *
   * @throws RestfulUnauthorizedException
   * @return \stdClass
   *   The user object.
   */
  public function getAccount(array $request = array(), $method = \RestfulInterface::GET, $cache = TRUE) {
    global $user;

    // Return the previously resolved user, if any.
    if (!empty($this->account)) {
      return $this->account;
    }

    // Resolve the user based on the providers in the manager.
    $account = NULL;
    foreach ($this as $provider) {
      if ($provider->applies($request, $method) && ($account = $provider->authenticate($request, $method)) && $account->uid && $account->status) {
        // The account has been loaded, we can stop looking.
        break;
      }
    }

    if (empty($account->uid) || !$account->status) {

      if ($this->count() && !$this->getIsOptional()) {
        // Allow caching pages for anonymous users.
        drupal_page_is_cacheable(variable_get('restful_page_cache', FALSE));

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
    if ($cache) {
      $this->setAccount($account);
    }

    // Disable page caching for security reasons so that an authenticated user
    // response never gets into the page cache for anonymous users.
    // This is necessary because the page cache system only looks at session
    // cookies, but not at HTTP Basic Auth headers.
    drupal_page_is_cacheable(!$account->uid && variable_get('restful_page_cache', FALSE));

    // Record the access time of this request.
    $this->setAccessTime($account);

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
    $this->switchUser();
  }

  /**
   * Switch the user to the user authenticated by RESTful.
   *
   * @link https://www.drupal.org/node/218104
   */
  public function switchUser() {
    global $user;

    if (!restful_is_user_switched() && !$this->getOriginalUserSession()) {
      // This is the first time a user switched, and there isn't an original
      // user session.

      $session = drupal_save_session();
      $this->setOriginalUserSession(array(
        'user' => $user,
        'session' => $session,
      ));

      // Don't allow a session to be saved. Provider that require a session to
      // be saved, like the cookie provider, need to explicitly set
      // drupal_save_session(TRUE).
      // @see \RestfulUserLoginCookie::loginUser().
      drupal_save_session(FALSE);
    }

    $account = $this->getAccount();
    // Set the global user.
    $user = $account;

  }

  /**
   * Switch the user to the authenticated user, and back.
   *
   * This should be called only for an API call. It should not be used for calls
   * via the menu system, as it might be a login request, so we avoid switching
   * back to the anonymous user.
   */
  public function switchUserBack() {
    global $user;
    if (!$user_state = $this->getOriginalUserSession()) {
      return;
    }

    $user = $user_state['user'];
    drupal_save_session($user_state['session']);
  }

  /**
   * Set the original user object and session.
   *
   * @param array $original_user_session
   *   Array keyed by 'user' and 'session'.
   */
  protected function setOriginalUserSession(array $original_user_session) {
    $this->originalUserSession = $original_user_session;
  }

  /**
   * Get the original user object and session.
   *
   * @return array
   *   Array keyed by 'user' and 'session'.
   */
  protected function getOriginalUserSession() {
    return $this->originalUserSession;
  }

  /**
   * Set the user's last access time.
   *
   * @param object $account
   *   A user account.
   *
   * @see _drupal_session_write()
   */
  protected function setAccessTime($account) {
    // This logic is pulled directly from _drupal_session_write().
    if ($account->uid && REQUEST_TIME - $account->access > variable_get('session_write_interval', 180)) {
      db_update('users')->fields(array(
        'access' => REQUEST_TIME,
      ))->condition('uid', $account->uid)->execute();
    }
  }
}

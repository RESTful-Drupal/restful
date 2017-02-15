<?php

/**
 * @file
 * Contains \Drupal\restful\Authentication\AuthenticationManager
 */

namespace Drupal\restful\Authentication;

use Drupal\restful\Exception\UnauthorizedException;
use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\AuthenticationPluginManager;
use Drupal\restful\RestfulManager;

/**
 * Class AuthenticationManager.
 *
 * @package Drupal\restful\Authentication
 */
class AuthenticationManager implements AuthenticationManagerInterface {

  /**
   * The resolved user object.
   *
   * @var object
   */
  protected $account;

  /**
   * The authentication plugins.
   *
   * @var AuthenticationPluginCollection
   */
  protected $plugins;

  /**
   * Determines if authentication is optional.
   *
   * If FALSE, then UnauthorizedException is thrown if no authentication
   * was found. Defaults to FALSE.
   *
   * @var bool
   */
  protected $isOptional = FALSE;

  /**
   * User session state to switch user for the Drupal thread.
   *
   * @var UserSessionStateInterface
   */
  protected $userSessionState;

  /**
   * Constructs a new AuthenticationManager object.
   *
   * @param AuthenticationPluginManager $manager
   *   The authentication plugin manager.
   */
  public function __construct(AuthenticationPluginManager $manager = NULL, UserSessionStateInterface $user_session_state = NULL) {
    $this->plugins = new AuthenticationPluginCollection($manager ?: AuthenticationPluginManager::create());
    $this->userSessionState = $user_session_state ?: new UserSessionState();
  }

  /**
   * {@inheritdoc}
   */
  public function setIsOptional($is_optional) {
    $this->isOptional = $is_optional;
  }

  /**
   * {@inheritdoc}
   */
  public function getIsOptional() {
    return $this->isOptional;
  }

  /**
   * {@inheritdoc}
   */
  public function addAuthenticationProvider($plugin_id) {
    $manager = AuthenticationPluginManager::create();
    $instance = $manager->createInstance($plugin_id);
    // The get method will instantiate a plugin if not there.
    $this->plugins->setInstanceConfiguration($plugin_id, $manager->getDefinition($plugin_id));
    $this->plugins->set($plugin_id, $instance);
  }

  /**
   * {@inheritdoc}
   */
  public function addAllAuthenticationProviders() {
    $manager = AuthenticationPluginManager::create();
    foreach ($manager->getDefinitions() as $id => $plugin) {
      $this->addAuthenticationProvider($id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAccount(RequestInterface $request, $cache = TRUE) {
    global $user;
    // Return the previously resolved user, if any.
    if (!empty($this->account)) {
      return $this->account;
    }
    // Resolve the user based on the providers in the manager.
    $account = NULL;
    foreach ($this->plugins as $provider) {
      /* @var \Drupal\restful\Plugin\authentication\AuthenticationInterface $provider */
      if ($provider->applies($request) && ($account = $provider->authenticate($request)) && $account->uid && $account->status) {
        // The account has been loaded, we can stop looking.
        break;
      }
    }

    if (empty($account->uid) || !$account->status) {

      if (RestfulManager::isRestfulPath($request) && $this->plugins->count() && !$this->getIsOptional()) {
        // Allow caching pages for anonymous users.
        drupal_page_is_cacheable(variable_get('restful_page_cache', FALSE));

        // User didn't authenticate against any provider, so we throw an error.
        throw new UnauthorizedException('Bad credentials. Anonymous user resolved for a resource that requires authentication.');
      }

      // If the account could not be authenticated default to the global user.
      // Most of the cases the cookie provider will do this for us.
      $account = drupal_anonymous_user();

      if (!$request->isViaRouter()) {
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
   * {@inheritdoc}
   */
  public function setAccount($account) {
    $this->account = $account;
    if (!empty($account->uid)) {
      $this->userSessionState->switchUser($account);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function switchUserBack() {
    return $this->userSessionState->switchUserBack();
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugins() {
    return $this->plugins;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugin($instance_id) {
    return $this->plugins->get($instance_id);
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

<?php

/**
 * @file
 * Contains \Drupal\restful\Authentication\AuthenticationManager
 */

namespace Drupal\restful\Authentication;

use Drupal\restful\Plugin\AuthenticationPluginManager;
use Drupal\restful\Plugin\authentication\AuthenticationInterface;

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
   * If FALSE, then \RestfulUnauthorizedException is thrown if no authentication
   * was found. Defaults to FALSE.
   *
   * @var bool
   */
  protected $isOptional = FALSE;

  /**
   * @param AuthenticationPluginManager $manager
   *   The authentication plugin manager.
   */
  public function __construct(AuthenticationPluginManager $manager = NULL) {
    $this->plugins = new AuthenticationPluginCollection($manager ?: AuthenticationPluginManager::create());
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
  public function addAuthenticationProvider($instance_id) {
    // The get method will instantiate a plugin if not there.
    $this->plugins->get($instance_id);
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
  public function getAccount(array $request = array(), $method = \RestfulInterface::GET, $cache = TRUE) {
    global $user;
    // Return the previously resolved user, if any.
    if (!empty($this->account)) {
      return $this->account;
    }
    // Resolve the user based on the providers in the manager.
    $account = NULL;
    foreach ($this->plugins as $provider) {
      if ($provider->applies($request, $method) && $account = $provider->authenticate($request, $method)) {
        // The account has been loaded, we can stop looking.
        break;
      }
    }

    if (!$account) {

      if ($this->plugins->count() && !$this->getIsOptional()) {
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
    return $account;
  }

  /**
   * {@inheritdoc}
   */
  public function setAccount($account) {
    $this->account = $account;
  }

}

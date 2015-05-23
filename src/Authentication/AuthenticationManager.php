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
   * Constructs a new AuthenticationManager object.
   *
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
      /** @var \Drupal\restful\Plugin\authentication\AuthenticationInterface $provider */
      if ($provider->applies($request) && $account = $provider->authenticate($request)) {
        // Allow caching pages for anonymous users.
        drupal_page_is_cacheable(variable_get('restful_page_cache', FALSE));

        // The account has been loaded, we can stop looking.
        break;
      }
    }

    if (!$account) {

      if (RestfulManager::isRestfulPath($request) && $this->plugins->count() && !$this->getIsOptional()) {
        // User didn't authenticate against any provider, so we throw an error.
        throw new UnauthorizedException('Bad credentials');
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
    return $account;
  }

  /**
   * {@inheritdoc}
   */
  public function setAccount($account) {
    $this->account = $account;
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

}

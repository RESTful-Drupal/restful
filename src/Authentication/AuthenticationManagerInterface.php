<?php

/**
 * @file
 * Contains \Drupal\restful\Authentication\AuthenticationManagerInterface
 */

namespace Drupal\restful\Authentication;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use \Drupal\restful\Exception\UnauthorizedException;
use \Drupal\restful\Plugin\Authentication\AuthenticationInterface;
use \Drupal\restful\Plugin\AuthenticationPluginManager;
use \Drupal\restful\Http\RequestInterface;

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
   * @param RequestInterface $request
   *   The request.
   * @param bool $cache
   *   Boolean indicating if the resolved user should be cached for next calls.
   *
   * @throws UnauthorizedException
   *   When bad credentials are provided.
   *
   * @return object
   *   The user object.
   */
  public function getAccount(RequestInterface $request, $cache = TRUE);

  /**
   * Setter method for the account property.
   *
   * @param object $account
   *   The account to set.
   */
  public function setAccount($account);

  /**
   * Switches the user back from the original user for the session.
   */
  public function switchUserBack();

  /**
   * Gets the plugin collection for this plugin manager.
   *
   * @return AuthenticationPluginManager
   *   The plugin manager.
   */
  public function getPlugins();

  /**
   * Get an authentication plugin instance by instance ID.
   *
   * @param string $instance_id
   *   The instance ID.
   *
   * @return AuthenticationInterface
   *   The plugin.
   *
   * @throws PluginNotFoundException
   *   If the plugin instance cannot be found.
   */
  public function getPlugin($instance_id);

}

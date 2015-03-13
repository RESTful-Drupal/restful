<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\AuthenticatedResourceInterface.
 */

namespace Drupal\restful\Plugin\resource;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\restful\Authentication\AuthenticationManager;

interface AuthenticatedResourceInterface extends PluginInspectionInterface {

  /**
   * Setter for $authenticationManager.
   *
   * @param AuthenticationManager $authentication_manager
   *   The authentication manager.
   */
  public function setAuthenticationManager(AuthenticationManager $authentication_manager);

  /**
   * Getter for $authenticationManager.
   *
   * @return AuthenticationManager
   *   The authentication manager.
   */
  public function getAuthenticationManager();

}

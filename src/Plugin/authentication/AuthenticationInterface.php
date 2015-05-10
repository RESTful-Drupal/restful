<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\authentication\Authentication
 */

namespace Drupal\restful\Plugin\authentication;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\restful\Http\RequestInterface;

interface AuthenticationInterface extends PluginInspectionInterface {

  /**
   * Authenticate the request by trying to match a user.
   *
   * @param RequestInterface $request
   *   The request.
   *
   * @return object
   *   The user object.
   */
  public function authenticate(RequestInterface $request);

  /**
   * Determines if the request can be checked for authentication. For example,
   * when authenticating with HTTP header, return FALSE if the header values do
   * not exist.
   *
   * @param RequestInterface $request
   *   The request.
   *
   * @return bool
   *   TRUE if the request can be checked for authentication, FALSE otherwise.
   */
  public function applies(RequestInterface $request);

  /**
   * Get the name of the authentication plugin.
   *
   * @return string
   *   The name.
   */
  public function getName();

}

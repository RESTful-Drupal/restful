<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\authentication\CookieAuthentication
 */

namespace Drupal\restful\Plugin\authentication;
use Drupal\restful\Exception\BadRequestException;
use Drupal\restful\Exception\ForbiddenException;
use Drupal\restful\Http\RequestInterface;
use Drupal\restful\RestfulManager;

/**
 * Class CookieAuthentication
 * @package Drupal\restful\Plugin\authentication
 *
 * @Authentication(
 *   id = "cookie",
 *   label = "Cookie based authentication",
 *   description = "Authenticate requests based on the user cookie.",
 * )
 */
class CookieAuthentication extends Authentication {

  /**
   * {@inheritdoc}
   */
  public function authenticate(RequestInterface $request) {
    if (!drupal_session_started() && !$this->isCli($request)) {
      return NULL;
    }

    global $user;
    $account = user_load($user->uid);

    if (!$request::isWriteMethod($request->getMethod()) || $request->getApplicationData('rest_call')) {
      // Request is done via API not CURL, or not a write operation, so we don't
      // need to check for a CSRF token.
      return $account;
    }

    if (!RestfulManager::isRestfulPath($request)) {
      return $account;
    }
    if (!$request->getCsrfToken()) {
      throw new BadRequestException('No CSRF token passed in the HTTP header.');
    }
    if (!drupal_valid_token($request->getCsrfToken(), Authentication::TOKEN_VALUE)) {
      throw new ForbiddenException('CSRF token validation failed.');
    }

    // CSRF validation passed.
    return $account;
  }

  /**
   * Detects whether the script is running from a command line environment.
   *
   * @param RequestInterface $request.
   *   The request.
   *
   * @return bool
   *   TRUE if a command line environment is detected. FALSE otherwise.
   */
  protected function isCli(RequestInterface $request) {
    // Needed to detect if run-tests.sh is running the tests.
    $cli = $request->getHeaders()->get('User-Agent')->getValueString() == 'Drupal command line';
    return $cli || drupal_is_cli();
  }

}

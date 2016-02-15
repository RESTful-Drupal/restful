<?php

/**
 * @file
 * Contains \Drupal\restful_token_auth\Plugin\authentication\TokenAuthentication
 */

namespace Drupal\restful_token_auth\Plugin\authentication;

use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\authentication\Authentication;

/**
 * Class TokenAuthentication
 * @package Drupal\restful\Plugin\authentication
 *
 * @Authentication(
 *   id = "token",
 *   label = "Token based authentication",
 *   description = "Authenticate requests based on the token sent in the request.",
 *   options = {
 *     "paramName" = "access_token",
 *   },
 * )
 */
class TokenAuthentication extends Authentication {

  /**
   * {@inheritdoc}
   */
  public function applies(RequestInterface $request) {
    return (bool) $this->extractToken($request);
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(RequestInterface $request) {
    // Access token may be on the request, or in the headers.
    if (!$token = $this->extractToken($request)) {
      return NULL;
    }

    // Check if there is a token that has not expired yet.
    $query = new \EntityFieldQuery();
    $result = $query
      ->entityCondition('entity_type', 'restful_token_auth')
      ->entityCondition('bundle', 'access_token')
      ->propertyCondition('token', $token)
      ->range(0, 1)
      ->execute();


    if (empty($result['restful_token_auth'])) {
      // No token exists.
      return NULL;
    }

    $id = key($result['restful_token_auth']);
    $auth_token = entity_load_single('restful_token_auth', $id);

    if (!empty($auth_token->expire) && $auth_token->expire < REQUEST_TIME) {
      // Token is expired.

      if (variable_get('restful_token_auth_delete_expired_tokens', TRUE)) {
        // Token has expired, so we can delete this token.
        $auth_token->delete();
      }

      return NULL;
    }

    return user_load($auth_token->uid);
  }

  /**
   * Extract the token from the request.
   *
   * @param RequestInterface $request
   *   The request.
   *
   * @return string
   *   The extracted token.
   */
  protected function extractToken(RequestInterface $request) {
    $plugin_definition = $this->getPluginDefinition();
    $options = $plugin_definition['options'];
    $key_name = !empty($options['paramName']) ? $options['paramName'] : 'access_token';

    // Access token may be on the request, or in the headers.
    $input = $request->getParsedInput();

    // If we don't have a $key_name on either the URL or the in the headers,
    // then check again using a hyphen instead of an underscore. This is due to
    // new versions of Apache not accepting headers with underscores.
    if (empty($input[$key_name]) && !$request->getHeaders()->get($key_name)->getValueString()) {
      $key_name = str_replace('_', '-', $key_name);
    }

    return empty($input[$key_name]) ? $request->getHeaders()->get($key_name)->getValueString() : $input[$key_name];
  }

}

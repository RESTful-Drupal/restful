<?php
/**
 * @file
 * Contains RestfulAuthenticationToken.
 */

class RestfulAuthenticationToken extends \RestfulAuthenticationBase {

  /**
   * Extracting the token from a request by a key name, either dashed or not.
   *
   * @param $param_name
   *  The param name to check.
   * @param array $request
   *  The current request.
   *
   * @return string
   *  The token from the request or FALSE if token isn't exists.
   */
  protected function extractTokenFromRequest(array $request = array(), $param_name) {
    $key_name = !empty($param_name) ? $param_name : 'access_token';
    $dashed_key_name = str_replace('_', '-', $key_name);

    // Access token may be on the request, or in the headers
    // (may be a with dash instead of underscore).
    if (!empty($request['__application'][$key_name])) {
      return $request['__application'][$key_name];
    }
    elseif (!empty($request[$key_name])) {
      return $request[$key_name];
    }
    elseif (!empty($request['__application'][$dashed_key_name])) {
      return $request['__application'][$dashed_key_name];
    }
    elseif (!empty($request[$dashed_key_name])) {
      return $request[$dashed_key_name];
    }

    // Access token with that key name isn't exists.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(array $request = array(), $method = \RestfulInterface::GET) {
    $options = $this->getPluginKey('options');

    return $this->extractTokenFromRequest($request, $options['param_name']);
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(array $request = array(), $method = \RestfulInterface::GET) {
    $options = $this->getPluginKey('options');
    $token = $this->extractTokenFromRequest($request, $options['param_name']);

    // Check if there is a token that did not expire yet.

    $query = new EntityFieldQuery();
    $result = $query
      ->entityCondition('entity_type', 'restful_token_auth')
      ->entityCondition('bundle', 'access_token')
      ->propertyCondition('token', $token)
      ->range(0, 1)
      ->execute();


    if (empty($result['restful_token_auth'])) {
      // No token exists.
      return;
    }

    $id = key($result['restful_token_auth']);
    $auth_token = entity_load_single('restful_token_auth', $id);

    if (!empty($auth_token->expire) && $auth_token->expire < REQUEST_TIME) {
      // Token is expired.

      if (variable_get('restful_token_auth_delete_expired_tokens', TRUE)) {
        // Token has expired, so we can delete this token.
        $auth_token->delete();
      }

      return;
    }

    return user_load($auth_token->uid);
  }
}

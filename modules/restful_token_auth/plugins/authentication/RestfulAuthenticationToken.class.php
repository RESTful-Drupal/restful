<?php
/**
 * @file
 * Contains RestfulAuthenticationToken.
 */

class RestfulAuthenticationToken extends \RestfulAuthenticationBase {

  /**
   * {@inheritdoc}
   */
  public function applies(array $request = array(), $method = \RestfulInterface::GET) {
    $options = $this->getPluginKey('options');
    $key_name = !empty($options['param_name']) ? $options['param_name'] : 'access_token';

    // Access token may be on the request, or in the headers.
    return !empty($request['__application'][$key_name]) || !empty($request[$key_name]);
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(array $request = array(), $method = \RestfulInterface::GET) {
    $options = $this->getPluginKey('options');
    $key_name = !empty($options['param_name']) ? $options['param_name'] : 'access_token';
    $token = !empty($request['__application'][$key_name]) ? $request['__application'][$key_name] : $request[$key_name];

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

<?php
/**
 * @file
 * Contains RestfulAuthenticationToken.
 */

class RestfulAuthenticationToken extends \RestfulAuthenticationBase implements \RestfulAuthenticationInterface {

  /**
   * {@inheritdoc}
   */
  public function applies($request = NULL) {
    $key_name = !empty($this->plugin['options']['param_name']) ? $this->plugin['options']['param_name'] : 'access_token';
    return !empty($request[$key_name]);
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate($request = array()) {
    $key_name = !empty($this->plugin['options']['param_name']) ? $this->plugin['options']['param_name'] : 'access_token';

    // Check if there is a token that did not expire yet.
    $query = new EntityFieldQuery();
    $result = $query
      ->entityCondition('entity_type', 'restful_token_auth')
      ->propertyCondition('token', $request[$key_name])
      ->range(0, 1)
      ->execute();

    if (empty($result['restful_token_auth'])) {
      // No token exists.
      return;
    }

    $id = key($result['restful_token_auth']);
    $auth_token = entity_load_single('restful_token_auth', $id);

    if (!empty($auth_token->expire) && $auth_token->expire > REQUEST_TIME) {
      // Token is expired.
      return;
    }

    return user_load($auth_token->uid);
  }
}

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
    return !empty($request['access_token']);
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate($request = array()) {
    // Check if there is a token that did not expire yet.
    $query = new EntityFieldQuery();
    $result = $query
      ->entityCondition('entity_type', 'restful_token_auth')
      ->propertyCondition('token', $request['access_token'])
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

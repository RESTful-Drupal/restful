<?php
/**
 * @file
 * Contains RestfulAuthenticationToken.
 */

class RestfulAuthenticationToken extends \RestfulAuthenticationBase {

  /**
   * {@inheritdoc}
   */
  public function applies() {
    return !empty($_GET['access_token']);
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate() {
    // Check if there is a token that did not expire yet.
    $query = new EntityFieldQuery();
    $result = $query
      ->entityCondition('entity_type', 'restful_auth_token')
      ->propertyCondition('token', $_GET['access_token'])
      ->range(0, 1)
      ->execute();

    if (empty($result['restful_auth_token'])) {
      // No token exists.
      return;
    }

    $id = key($result['restful_auth_token']);
    $auth_token = entity_load_single('restful_auth_token', $id);

    // Return TRUE only if there is no expire value, or the expire is in the
    // future.
    return !empty($auth_token->expire) ? $auth_token->expire > REQUEST_TIME : TRUE;
  }
}

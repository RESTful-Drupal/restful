<?php

/**
 * @file
 * Contains RestfulRefreshTokenAuthentication.
 */

class RestfulRefreshTokenAuthentication extends \RestfulTokenAuthenticationBase {

  /**
   * Overrides \RestfulBase::controllersInfo().
   */
  public static function controllersInfo() {
    return array(
      '.*' => array(
        // Get or create a new token.
        \RestfulInterface::GET => 'refreshToken',
      ),
    );
  }

  /**
   * Create a token for a user, and return its value.
   *
   * @param string $token
   *   The refresh token.
   *
   * @throws RestfulBadRequestException
   *
   * @return \RestfulTokenAuth
   *   The new access token.
   */
  public function refreshToken($token) {
    // Check if there is a token that did not expire yet.
    $query = new EntityFieldQuery();
    $results = $query
      ->entityCondition('entity_type', $this->entityType)
      ->entityCondition('bundle', 'refresh_token')
      ->propertyCondition('token', $token)
      ->range(0, 1)
      ->execute();

    if (empty($results['restful_token_auth'])) {
      throw new \RestfulBadRequestException('Invalid refresh token.');
    }

    // Remove the refresh token once used.
    $refresh_token = entity_load_single('restful_token_auth', key($results['restful_token_auth']));
    $uid = $refresh_token->uid;

    // Get the access token linked to this refresh token then do some cleanup.
    $accessTokenQuery = new EntityFieldQuery();
    $accessTokenReference = $accessTokenQuery
      ->entityCondition('entity_type', 'restful_token_auth')
      ->entityCondition('bundle', 'access_token')
      ->fieldCondition('refresh_token_reference', 'target_id', $refresh_token->id)
      ->range(0, 1)
      ->execute();

    if (!empty($accessTokenReference['restful_token_auth'])) {
      $access_token = key($accessTokenReference['restful_token_auth']);
      entity_delete('access_token', $refresh_token->id);
      entity_delete('restful_token_auth', $access_token);
    }

    $refresh_token->delete();

    // Create the new access token and return it.
    $controller = entity_get_controller($this->getEntityType());
    $token = $controller->generateAccessToken($uid);
    return $this->viewEntity($token->id);
  }

}

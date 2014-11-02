<?php

/**
 * @file
 * Contains RestfulRefreshTokenAuthentication.
 */

class RestfulRefreshTokenAuthentication extends \RestfulEntityBase {

  /**
   * Overrides RestfulEntityBase::publicFieldsInfo().
   *
   * Keep only the "token" property.
   */
  public function publicFieldsInfo() {
    $public_fields['access_token'] = array(
      'property' => 'token',
    );
    $public_fields['type'] = array(
      'callback' => array('\RestfulManager::echoMessage', array('Bearer')),
    );
    $public_fields['expires_in'] = array(
      'property' => 'expire',
      'process_callbacks' => array(
        'static::intervalInSeconds',
      ),
    );
    $public_fields['refresh_token'] = array(
      'property' => 'refresh_token_reference',
      'process_callbacks' => array(
        'static::getTokenFromEntity',
      ),
    );

    return $public_fields;
  }

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
    $account = $this->getAccount();
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
    $refresh_token->delete();

    // Create the new access token and return it.
    $controller = entity_get_controller($this->getEntityType());
    $token = $controller->createAccessToken($account->uid);
    return $this->viewEntity($token->id);
  }

  /**
   * Process callback helper to get the time difference in seconds.
   *
   * @param int $value
   *   The expiration timestamp in the access token.
   *
   * @return int
   *   Number of seconds before expiration.
   */
  public static function intervalInSeconds($value) {
    $interval = $value - time();
    return $interval < 0 ? 0 : $interval;
  }

  /**
   * Get the token string from the token entity.
   *
   * @param \RestfulTokenAuth $token
   *   The restful_token_auth entity.
   *
   * @return string
   *   The token string.
   */
  public static function getTokenFromEntity(\RestfulTokenAuth $token) {
    return $token->token;
  }

}

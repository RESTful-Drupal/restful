<?php

/**
 * @file
 * Contains \RestfulTokenAuthenticationBase
 */

class RestfulTokenAuthenticationBase extends \RestfulEntityBase {

  /**
   * Overrides RestfulEntityBase::publicFieldsInfo().
   *
   * @see http://tools.ietf.org/html/rfc6750#section-4
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

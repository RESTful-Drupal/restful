<?php
/**
 * @file
 * Contains Drupal\restful_token_auth\Plugin\resource\TokenAuthenticationBase.
 */

namespace Drupal\restful_token_auth\Plugin\resource;


use Drupal\restful\Plugin\resource\ResourceEntity;
use Drupal\restful\Plugin\resource\ResourceInterface;

abstract class TokenAuthenticationBase extends ResourceEntity implements ResourceInterface {

  /**
   * Overrides ResourceEntity::publicFields().
   *
   * @see http://tools.ietf.org/html/rfc6750#section-4
   */
  public function publicFields() {
    $public_fields = parent::publicFields();
    unset($public_fields['label']);
    unset($public_fields['self']);
    $public_fields['id']['methods'] = array();
    $public_fields['access_token'] = array(
      'property' => 'token',
    );
    $public_fields['type'] = array(
      'callback' => array('\Drupal\restful\RestfulManager::echoMessage', array('Bearer')),
    );
    $public_fields['expires_in'] = array(
      'property' => 'expire',
      'process_callbacks' => array(
        '\Drupal\restful_token_auth\Plugin\resource\TokenAuthenticationBase::intervalInSeconds',
      ),
    );
    $public_fields['refresh_token'] = array(
      'property' => 'refresh_token_reference',
      'process_callbacks' => array(
        '\Drupal\restful_token_auth\Plugin\resource\TokenAuthenticationBase::getTokenFromEntity',
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
   * @param int $token_id
   *   The restful_token_auth entity.
   *
   * @return string
   *   The token string.
   */
  public static function getTokenFromEntity($token_id) {
    if ($token = entity_load_single('restful_token_auth', $token_id)) {
      return $token->token;
    }
    return NULL;
  }

}

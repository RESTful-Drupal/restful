<?php

/**
 * @file
 * Contains Drupal\restful_token_auth\Entity\RestfulTokenAuthController.
 */

namespace Drupal\restful_token_auth\Entity;

use Drupal\restful\Exception\ServerConfigurationException;

class RestfulTokenAuthController extends \EntityAPIController {

  /**
   * Create a new access_token entity with a referenced refresh_token.
   *
   * @param int $uid
   *   The user ID.
   *
   * @return RestfulTokenAuth
   *   The created entity.
   */
  public function generateAccessToken($uid) {
    $refresh_token = $this->generateRefreshToken($uid);
    // Create a new access token.
    $values = array(
      'uid' => $uid,
      'type' => 'access_token',
      'created' => REQUEST_TIME,
      'name' => t('Access token for: @uid', array(
        '@uid' => $uid,
      )),
      'token' => drupal_random_key(),
      'expire' => $this->getExpireTime(),
      'refresh_token_reference' => array(
        LANGUAGE_NONE => array(array(
          'target_id' => $refresh_token->id,
        )),
      ),
    );
    $access_token = $this->create($values);
    $this->save($access_token);

    return $access_token;
  }

  /**
   * Create a refresh token for the current user.
   *
   * It will delete all the existing refresh tokens for that same user as well.
   *
   * @param int $uid
   *   The user ID.
   *
   * @return RestfulTokenAuth
   *   The token entity.
   */
  private function generateRefreshToken($uid) {
    // Create a new refresh token.
    $values = array(
      'uid' => $uid,
      'type' => 'refresh_token',
      'created' => REQUEST_TIME,
      'name' => t('Refresh token for: @uid', array(
        '@uid' => $uid,
      )),
      'token' => drupal_random_key(),
    );
    $refresh_token = $this->create($values);
    $this->save($refresh_token);
    return $refresh_token;
  }

  /**
   * Return the expiration time.
   *
   * @throws ServerConfigurationException
   *
   * @return int
   *   Timestamp with the expiration time.
   */
  protected function getExpireTime() {
    $now = new \DateTime();
    try {
      $expiration = $now->add(new \DateInterval(variable_get('restful_token_auth_expiration_period', 'P1D')));
    }
    catch (\Exception $e) {
      throw new ServerConfigurationException('Invalid DateInterval format provided.');
    }
    return $expiration->format('U');
  }

}

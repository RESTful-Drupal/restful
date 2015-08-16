<?php

/**
 * @file
 * Contains \RestfulTokenAuthController
 */

class RestfulTokenAuthController extends \EntityAPIController {

  /**
   * Create a new access_token entity with a referenced refresh_token.
   *
   * @param int $uid
   *   The user ID.
   *
   * @return \RestfulTokenAuth
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
      'refresh_token_reference' => array(LANGUAGE_NONE => array(array(
        'target_id' => $refresh_token->id,
      ))),
    );
    $access_token = $this->create($values);
    $this->save($access_token);

    return $access_token;
  }

  /**
   * Create a refresh token for the current user
   *
   * It will delete all the existing refresh tokens for that same user as well.
   *
   * @param int $uid
   *   The user ID.
   *
   * @return \RestfulTokenAuth
   *   The token entity.
   */
  private function generateRefreshToken($uid) {
    $this->deleteToken($uid, 'refresh_token');

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
   * Delete tokens for the current user.
   *
   * @param  int $uid
   *   The user ID.
   *
   * @param string $bundle
   *   The bundle (refresh_token or access_token) that should be deleted.
   *
   * @return bool
   *   If the token(s) were deleted or not.
   */
  private function deleteToken($uid, $bundle = NULL) {
    // Check if there are tokens for the user.
    $query = new \EntityFieldQuery();
    $results = $query
      ->entityCondition('entity_type', 'restful_token_auth')
      ->propertyCondition('uid', $uid);

    // Add a bundle if one has been passed, otherwise all tokens for the uid
    // will be deleted.
    if (!empty($bundle)) {
      $results->entityCondition('bundle', $bundle);
    }

    $results->execute();

    if (!empty($results['restful_token_auth'])) {
      // Delete the tokens.
      entity_delete_multiple('restful_token_auth', array_keys($results['restful_token_auth']));
    }
  }

  /**
   * Return the expiration time.
   *
   * @throws RestfulServerConfigurationException
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
      throw new \RestfulServerConfigurationException('Invalid DateInterval format provided.');
    }
    return $expiration->format('U');
  }

}

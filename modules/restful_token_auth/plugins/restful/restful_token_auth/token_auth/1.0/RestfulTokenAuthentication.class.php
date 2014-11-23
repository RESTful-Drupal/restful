<?php

/**
 * @file
 * Contains RestfulTokenAuth.
 */

class RestfulTokenAuthentication extends \RestfulEntityBase {

  /**
   * Overrides RestfulEntityBase::publicFieldsInfo().
   *
   * Keep only the "token" property.
   */
  public function publicFieldsInfo() {
    $public_fields['access_token'] = array(
      'property' => 'token',
    );
    return $public_fields;
  }

  /**
   * Overrides \RestfulBase::controllersInfo().
   */
  public static function controllersInfo() {
    return array(
      '' => array(
        // Get or create a new token.
        \RestfulInterface::GET => 'getOrCreateToken',
      ),
    );
  }

  /**
   * Create a token for a user, and return its value.
   */
  public function getOrCreateToken() {
    $account = $this->getAccount();
    // Check if there is a token that did not expire yet.
    $query = new EntityFieldQuery();
    $result = $query
      ->entityCondition('entity_type', $this->entityType)
      ->propertyCondition('uid', $account->uid)
      ->range(0, 1)
      ->execute();

    $token_exists = FALSE;

    if (!empty($result['restful_token_auth'])) {
      $id = key($result['restful_token_auth']);
      $auth_token = entity_load_single('restful_token_auth', $id);

      if (!empty($auth_token->expire) && $auth_token->expire < REQUEST_TIME) {
        if (variable_get('restful_token_auth_delete_expired_tokens', TRUE)) {
          // Token has expired, so we can delete this token.
          $auth_token->delete();
        }

        $token_exists = FALSE;
      }
      else {
        $token_exists = TRUE;
      }
    }

    if (!$token_exists) {
      // Create a new token.
      $values = array(
        'uid' => $account->uid,
        'type' => 'restful_token_auth',
        'created' => REQUEST_TIME,
        'name' => 'self',
        'token' => drupal_random_key(),
        'expire' => $this->getExpireTime(),
      );
      $auth_token = entity_create('restful_token_auth', $values);
      entity_save('restful_token_auth', $auth_token);
      $id = $auth_token->id;
    }

    $output = $this->viewEntity($id);

    return $output;
  }

  /**
   * Return the expiration time.
   *
   * @return int
   *   Timestamp with the expiration time.
   */
  protected function getExpireTime() {
    return strtotime('now + 1 week');
  }
}

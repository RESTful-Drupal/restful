<?php

/**
 * @file
 * Contains RestfulAccessTokenAuthentication.
 */

class RestfulAccessTokenAuthentication extends \RestfulTokenAuthenticationBase {

  /**
   * Overrides \RestfulBase::controllersInfo().
   */
  public static function controllersInfo() {
    return array(
      '' => array(
        // Get or create a new token.
        \RestfulInterface::GET => 'getOrCreateToken',
        // Delete the auth and refresh tokens.
        \RestfulInterface::DELETE => 'deleteUsersTokens',
      ),
    );
  }

  /**
   * Create a token for a user, and return its value.
   */
  public function getOrCreateToken() {
    $entity_type = $this->getEntityType();
    $account = $this->getAccount();
    // Check if there is a token that did not expire yet.
    $query = new EntityFieldQuery();
    $result = $query
      ->entityCondition('entity_type', $entity_type)
      ->entityCondition('bundle', 'access_token')
      ->propertyCondition('uid', $account->uid)
      ->range(0, 1)
      ->execute();

    $token_exists = FALSE;

    if (!empty($result[$entity_type])) {
      $id = key($result[$entity_type]);
      $access_token = entity_load_single($entity_type, $id);

      $token_exists = TRUE;
      if (!empty($access_token->expire) && $access_token->expire < REQUEST_TIME) {
        if (variable_get('restful_token_auth_delete_expired_tokens', TRUE)) {
          // Token has expired, so we can delete this token.
          $access_token->delete();
        }

        $token_exists = FALSE;
      }
    }

    if (!$token_exists) {
      $controller = entity_get_controller($this->getEntityType());
      $access_token = $controller->generateAccessToken($account->uid);
      $id = $access_token->id;
    }

    $output = $this->viewEntity($id);

    return $output;
  }

  /**
   * Delete the access token for the user submitting the request.
   */
  public function deleteUsersTokens() {
    $account = $this->getAccount();

    // Check if there are other refresh tokens for the user.
    $query = new \EntityFieldQuery();
    $results = $query
      ->entityCondition('entity_type', 'restful_token_auth')
      ->entityCondition('bundle', 'access_token')
      ->propertyCondition('uid', $account->uid)
      ->execute();

    if (!empty($results['restful_token_auth'])) {
      foreach (array_keys($results['restful_token_auth']) as $entity_id) {
        $this->isValidEntity('view', $entity_id);
        $wrapper = entity_metadata_wrapper($this->entityType, $entity_id);
        $wrapper->delete();
      }
    }

    $this->setHttpHeaders('Status', 204);
    return array();
  }

}

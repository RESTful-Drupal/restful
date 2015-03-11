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

    // various options to provide info about the token owner
    $owner_resource = variable_get('restful_token_auth_owner_resource', '');

    if ($owner_resource !== '<none>') {
      if ($owner_resource == '<id>') {
        $output['owner'] = $access_token->uid;
      } else {
        $handler = restful_get_restful_handler('users') ?: restful_get_restful_handler($owner_resource) ?: FALSE;
        if ($handler) {
          $user = $handler->get($access_token->uid);
          $output['owner'] = array_pop($user);
        }
      }
    }

    return $output;
  }
}

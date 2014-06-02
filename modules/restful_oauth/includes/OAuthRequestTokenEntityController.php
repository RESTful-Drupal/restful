<?php

/**
 * @file
 * Contains OAuthRequestTokenEntityController.
 */

class OAuthRequestTokenEntityController extends EntityAPIController {

  /**
   * Helper function to get an entity by consumer_key.
   *
   * @param string $request_token
   *   The consumer key to match.
   *
   * @return stdClass
   *   The fully loaded entity.
   */
  public function loadByRequestToken($request_token) {
    $id = $this->getIdByRequestToken($request_token);
    return $id ? entity_load_single('restful_request_token', $id) : NULL;
  }

  /**
   * Helper function to get an entity by consumer_key.
   *
   * @param string $request_token
   *   The consumer key to match.
   *
   * @return int
   *   The entity ID or NULL if none could be found.
   */
  public function getIdByRequestToken($request_token) {
    // Request tokens can only be used once, so if there is already a user
    // associated with this one, something is wrong and we should not accept it.
    $query = "SELECT id FROM {restful_request_token} WHERE request_token = :request_token AND uid IS NULL";
    $id = db_query_range($query, 0, 1, array(
      ':request_token' => $request_token,
    ))->fetchField();
    return $id ? $id : NULL;
  }

}

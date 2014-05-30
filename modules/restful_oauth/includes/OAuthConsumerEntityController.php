<?php

/**
 * @file
 * Contains OAuthConsumerEntityController.
 */

class OAuthConsumerEntityController extends EntityAPIController {

  /**
   * Helper function to get an entity by consumer_key.
   *
   * @param string $consumer_key
   *   The consumer key to match.
   *
   * @return stdClass
   *   The fully loaded consumer.
   */
  public function loadByConsumerKey($consumer_key) {
    // Find the consumer by consumer key.
    $query = new EntityFieldQuery();
    $result = $query
      ->entityCondition('entity_type', 'restful_oauth_controller')
      ->propertyCondition('consumer_key', $consumer_key)
      ->range(0, 1)
      ->execute();
    if(!empty($result['restful_oauth_controller'])) {
      $id = key($result['restful_oauth_controller']);
      return entity_load_single('restful_oauth_controller', $id);
    }
    return NULL;
  }

}

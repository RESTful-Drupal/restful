<?php

/**
 * @file
 * Contains RestfulOAuthConsumerEndpoint.
 */

class RestfulOAuthConsumerEndpoint extends \RestfulEntityBase {

  /**
   * Overrides RestfulEntityBase::getQueryForList().
   *
   * Keep only the "token" property.
   */
  public function getPublicFields() {
    $public_fields['id'] = array(
      'property' => 'id',
    );
    $public_fields['consumer_key'] = array(
      'property' => 'consumer_key',
    );
    $public_fields['consumer_secret'] = array(
      'property' => 'consumer_secret',
    );
    $public_fields['callback'] = array(
      'property' => 'callback',
    );
    return $public_fields;
  }

  /**
   * Overrides \RestfulEntityBase::controllers
   *
   * @var array
   */
  protected $controllers = array(
    '' => array(
      // Get or create a new token.
      'post' => 'registerConsumer',
    ),
    '\d+' => array(
      'get' => 'viewEntity',
      'patch' => 'patchEntity',
      'delete' => 'deleteEntity',
    ),
  );

  /**
   * Generate a random token.
   *
   * @param int $length
   *   Length of the token (max 40).
   *
   * @return string
   *   The random token.
   */
  public static function randomToken($length = 30) {
    $entropy = uniqid(mt_rand(), true) . time();
    $hash = sha1($entropy);  // sha1 gives us a 40-byte hash
    // The first 30 bytes should be plenty for the consumer_key
    // We use the last 10 for the shared secret
    return substr($hash, 0, $length);
  }

  /**
   * Create a token for a user, and return its value.
   */
  public function registerConsumer($request = NULL, stdClass $account = NULL) {
    if (empty($request['callback'])) {
      $e = new \RestfulBadRequestException();
      $e->getDescription('You need to include the callback parameter.');
      $e->setInstance('help/restful/problem-instances-missing-callback');
      throw $e;
    }
    // Create a new consumer.
    $consumer = entity_create($this->getEntityType(), array('type' => $this->getBundle()));

    if (!entity_access('create', $this->getEntityType(), $consumer, $account)) {
      // User does not have access to create entity.
      $params = array('@resource' => $this->plugin['label']);
      entity_delete($this->getEntityType(), $consumer->id);
      throw new RestfulForbiddenException(format_string('You do not have access to create a new @resource resource.', $params));
    }
    $consumer->created = REQUEST_TIME;
    $consumer->uid = $account->uid;
    $consumer->name = isset($request['name']) ? $request['name'] : t('Unnamed consumer');
    $consumer->consumer_key = static::randomToken(30);
    $consumer->consumer_secret = static::randomToken(10);
    $consumer->callback = $request['callback'];
    entity_save('restful_token_auth', $consumer);

    $output = $this->viewEntity($consumer->id, $request, $account);

    // Be HAL friendly and add some links with the consumer IDs for the current
    // user.
    $query = new EntityFieldQuery();
    $results = $query
      ->entityCondition('entity_type', 'restful_oauth_consumer')
      ->propertyCondition('uid', $account->uid)
      ->execute();
    if (!empty($results['restful_oauth_consumer'])) {
      $related_consumers = array();
      foreach (array_keys($results['restful_oauth_consumer']) as $id) {
        $related_consumers[] = array('id' => $id);
      }
      $output['_links'] = array(
        // TODO: Create an advanced help page to describe the oa:consumer curie.
        'oa:consumers' => $related_consumers,
      );
    }

    return $output;

  }
}

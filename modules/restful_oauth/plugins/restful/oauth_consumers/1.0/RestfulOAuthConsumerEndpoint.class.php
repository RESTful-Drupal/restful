<?php

/**
 * @file
 * Contains RestfulOAuthConsumerEndpoint.
 */

class RestfulOAuthConsumerEndpoint extends \RestfulOAuthEndpoint {

  /**
   * Overrides RestfulEntityBase::getQueryForList().
   *
   * Keep only the "token" property.
   */
  public function getPublicFields() {
    $public_fields = parent::getPublicFields();
    $public_fields += array(
      'consumer_key' => array(
        'property' => 'consumer_key',
      ),
      'consumer_secret' => array(
        'property' => 'consumer_secret',
      ),
      'callback' => array(
        'property' => 'callback',
      ),
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
      'post' => 'registerNewEntity',
    ),
    '\d+' => array(
      'get' => 'viewEntity',
      'patch' => 'patchEntity',
      'delete' => 'deleteEntity',
    ),
  );

  /**
   * Populates the new entity values with the appropriate ones in the $request.
   *
   * @param Entity $entity
   *   The newly created entity.
   *
   * @param array $request
   *   The request array.
   *
   * @throws RestfulBadRequestException
   */
  protected function populateEntity($entity, $request) {
    if (empty($request['callback'])) {
      $e = new \RestfulBadRequestException();
      $e->getDescription('You need to include the callback parameter.');
      $e->setInstance('help/restful/problem-instances-missing-callback');
      throw $e;
    }
    $entity->created = REQUEST_TIME;
    $entity->uid = $this->getAccount()->uid;
    $entity->name = isset($request['name']) ? $request['name'] : t('Unnamed consumer');
    $entity->consumer_key = static::randomToken(30);
    $entity->consumer_secret = static::randomToken(10);
    $entity->key_status = 0;
    $entity->callback = $request['callback'];
  }

  /**
   * {@inheritdoc}
   */
  protected function alterOutputData($output, \Entity $entity, $request) {
    // Be HAL friendly and add some links with the consumer IDs for the current
    // user.
    $query = new EntityFieldQuery();
    $results = $query
      ->entityCondition('entity_type', 'restful_oauth_consumer')
      ->propertyCondition('uid', $this->getAccount()->uid)
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

  /**
   * {@inheritdoc}
   */
  protected function isValidOAuthRequest() {
    // No check needed to request a consumer key pair.
  }

}

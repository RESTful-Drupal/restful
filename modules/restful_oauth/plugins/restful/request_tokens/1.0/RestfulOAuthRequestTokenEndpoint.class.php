<?php

/**
 * @file
 * Contains RestfulOAuthRequestTokenEndpoint.
 */

class RestfulOAuthRequestTokenEndpoint extends \RestfulOAuthEndpoint {

  /**
   * {@inheritdoc}
   *
   * @see http://oauth.net/core/1.0/#auth_step1
   */
  protected $requiredRequestParams = array();

  /**
   * Overrides RestfulEntityBase::getPublicFields().
   */
  public function getPublicFields() {
    $public_fields = parent::getPublicFields();
    $public_fields += array(
      'oauth_token' => array(
        'property' => 'request_token',
      ),
      'oauth_token_secret' => array(
        'property' => 'request_token_secret',
      ),
    );
    return $public_fields;
  }

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
   * @throws RestfulUnprocessableEntityException
   */
  protected function populateEntity($entity, $request) {
    if (empty($request['oauth_callback'])) {
      $e = new \RestfulBadRequestException('Missing callback.');
      $e->getDescription('You need to include the callback parameter.');
      $e->setInstance('help/restful/problem-instances-missing-parameter');
      throw $e;
    }
    $request += array(
      'oauth_version' => '1.0',
    );
    $consumer_controller = entity_get_controller('restful_oauth_consumer');
    $consumer = $consumer_controller->loadByConsumerKey($request['oauth_consumer_key']);
    if (empty($consumer)) {
      throw new \RestfulUnprocessableEntityException('Bad consumer key.');
    }
    $entity->created = REQUEST_TIME;
    $entity->uid = NULL;
    $entity->name = isset($request['name']) ? $request['name'] : t('Unnamed request token');
    $entity->request_token = static::randomToken(10);
    $entity->request_token_secret = static::randomToken(30);
    $entity->consumer_id = $consumer->id;
    $entity->callback = $request['oauth_callback'];
  }

  /**
   * {@inheritdoc}
   */
  protected function alterOutputData($output, \Entity $entity, $request) {
    // We need to comply with the standard since other OAuth libraries will rely
    // on a specific response.
    $output_vars = array();
    $output_vars['oauth_token'] = 'oauth_token=' . oauth_urlencode($output['oauth_token']);
    $output_vars['oauth_token_secret'] = 'oauth_token_secret=' . oauth_urlencode($output['oauth_token_secret']);
    $output_vars['oauth_callback_confirmed'] = 'oauth_callback_confirmed=true';
    ksort($output_vars);

    // We are returning plain text.
    drupal_add_http_header('Content-Type', 'application/text');
    echo implode('&', array_values($output_vars));
  }

  /**
   * {@inheritdoc}
   */
  protected function isValidOAuthRequest() {
    try {
      $this->oauthController->getRequestToken();
    }
    catch (\OAuthException $e) {
      throw new \RestfulForbiddenException('OAuth: ' . $e->getMessage());
    }
  }

}

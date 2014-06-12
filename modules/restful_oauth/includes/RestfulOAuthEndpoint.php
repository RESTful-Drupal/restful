<?php
/**
 * @file
 * Contains RestfulOAuthEndpoint.
 */

abstract class RestfulOAuthEndpoint extends \RestfulEntityBase {

  /**
   * OAuth request URIs.
   *
   * @see http://oauth.net/core/1.0/#request_urls
   */
  const REQUEST_TOKEN_URI = 'api/oauth/request_token';
  const USER_AUTHORIZATION_URI = 'api/user/login/oauth';
  const ACCESS_TOKEN_URI = 'api/oauth/access_token';

  /**
   * Required request params.
   *
   * @var array
   */
  protected $requiredRequestParams = array();

  /**
   * OAuth controller.
   *
   * @var RestfulOAuthController
   */
  protected $oauthController;

  /**
   *
   */
  public function __construct($plugin, \RestfulAuthenticationManager $auth_manager = NULL, \DrupalCacheInterface $cache_controller = NULL) {
    parent::__construct($plugin, $auth_manager, $cache_controller);
    $this->oauthController = new \RestfulOAuthController();
  }

  /**
   * Overrides RestfulEntityBase::getPublicFields().
   */
  public function getPublicFields() {
    $public_fields['id'] = array(
      'wrapper_method' => 'getIdentifier',
      'wrapper_method_on_entity' => TRUE,
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
      'post' => 'registerNewEntity'
    ),
    '\d+' => array(
      'get' => 'viewEntity',
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
  public function registerNewEntity($request = NULL, stdClass $account = NULL) {
    foreach ($this->requiredRequestParams as $param) {
      if (empty($request[$param])) {
        $e = new \RestfulBadRequestException('Missing required parameter.');
        $e->getDescription('You need to include the @param parameter.', array(
          '@param' => $param,
        ));
        $e->setInstance('help/restful/problem-instances-missing-parameter');
        throw $e;
      }
    }
    // This will check that the request is signed and conforms to the OAuth
    // specifications. In case it does not, then it will throw an exception.
    $this->isValidOAuthRequest();

    // If we get here it means that the request is legit. Meaning that:
    //   1. The consumer secret matches.
    //   2. The nonce and timestamp are valid.
    //   3. The whole request is correctly signed.

    // Create a new entity.
    $entity = entity_create($this->getEntityType(), array('type' => $this->getBundle()));

    if (!entity_access('create', $this->getEntityType(), $entity, $account)) {
      // User does not have access to create entity.
      $params = array('@resource' => $this->getPluginInfo('label'));
      list($id,,) = entity_extract_ids($this->getEntityType(), $entity);
      entity_delete($this->getEntityType(), $id);
      throw new RestfulForbiddenException(format_string('You do not have access to create a new @resource resource.', $params));
    }
    $this->populateEntity($entity, $request);
    entity_save('restful_token_auth', $entity);

    list($id,,) = entity_extract_ids($this->getEntityType(), $entity);
    $output = $this->viewEntity($id, $request, $account);

    $output = $this->alterOutputData($output, $entity, $request);

    return $output;

  }

  /**
   * Modifies the output data after the entity has been saved.
   *
   * @param array $output
   *   The output to be converted to JSON.
   * @param \Entity $entity
   *   The newly created entity.
   * @param array $request
   *   The request array.
   *
   * @return array
   *   The output array with the extra information.
   */
  protected function alterOutputData($output, \Entity $entity, $request) {
    return $output;
  }

  /**
   * @throws \OAuthException if the request is not valid.
   */
  abstract protected function isValidOAuthRequest();
}

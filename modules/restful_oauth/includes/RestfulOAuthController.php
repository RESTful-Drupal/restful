<?php

/**
 * @file
 * Contains RestfulOAuthController.
 */

class RestfulOAuthController {

  /**
   * The OAuth provider.
   *
   * @var OAuthProvider
   */
  public $provider;

  /**
   * Get request token.
   */
  public function getRequestToken() {
    $this->init();
    $this->provider->isRequestTokenEndpoint(TRUE);
    $this->provider->checkOAuthRequest(url(request_path(), array('absolute' => TRUE)), $_SERVER['REQUEST_METHOD']);
  }

  /**
   * Init.
   */
  public function init() {
    $this->cipher = new \Cipher();
    $this->provider = new OAuthProvider();
    $this->provider->consumerHandler(array($this,'lookupConsumer'));
    $this->provider->timestampNonceHandler(array($this,'timestampNonceChecker'));
    $this->provider->tokenHandler(array($this, 'tokenHandler'));
  }

  /**
   * Look up the consumer key and check if it is valid.
   *
   * @param OAuthProvider $provider
   *   The OAuth provider.
   *
   * @return int
   *   The OAuth code.
   */
  public function lookupConsumer(\OAuthProvider $provider) {
    $entity_controller = entity_get_controller('restful_oauth_consumer');
    $consumer = $entity_controller->loadByConsumerKey($provider->consumer_key);
    if (empty($consumer)) {
      return OAUTH_CONSUMER_KEY_UNKNOWN;
    }
    // 0 is active, 1 is throttled, 2 is blacklisted
    // TODO: Add throttling & blacklisting logic.
    if ($consumer->key_status != 0) {
      return OAUTH_CONSUMER_KEY_REFUSED;
    }
    $provider->consumer_secret = $consumer->consumer_secret;
    return OAUTH_OK;
  }

  /**
   * Timestamp nonce checker.
   *
   * Check whether the timestamp of the request is sane and falls within the
   * window of our Nonce checks. Also check whether the provided Nonce has been
   * used already to prevent replay attacks.
   *
   * @param \OAuthProvider $provider
   *   The OAuth provider.
   *
   * @return int
   *   The OAuth code.
   */
  public function timestampNonceChecker(\OAuthProvider $provider) {
    // The timestamp is here to prevent replaying of request token requests if
    // they are captured - it is normal to compare the given timestamp is within
    // a few minutes of the local server time.
    if (!$this->cipher->checkNonce($provider->nonce)) {
      return OAUTH_BAD_NONCE;
    }
    if ($provider->timestamp == 0 || time() - $provider->timestamp > variable_get('restful_oauth_timestamp_validity', 7200)) {
      return OAUTH_BAD_TIMESTAMP;
    }

    return OAUTH_OK;
  }

  /**
   * Check whether a request or access token is valid.
   *
   * @param \OAuthProvider $provider
   *   The OAuth provider.
   *
   * @return int
   *   The OAuth code.
   */
  public function tokenHandler(\OAuthProvider $provider) {
    if ($provider->token === 'rejected') {
      return OAUTH_TOKEN_REJECTED;
    } elseif ($provider->token === 'revoked') {
      return OAUTH_TOKEN_REVOKED;
    }

    $provider->token_secret = "the_tokens_secret";
    return OAUTH_OK;
  }

  /**
   * Menu callback to sign an OAuth request.
   */
  public function requestSign() {
    return array('foo' => 'bar');
  }

}

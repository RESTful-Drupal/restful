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
  protected $provider;

  /**
   * Get the token.
   */
  public function requestToken() {
    $this->provider = new OAuthProvider();
    $this->provider->consumerHandler(array($this,'lookupConsumer'));
    $this->provider->timestampNonceHandler(array($this,'timestampNonceChecker'));
    $this->provider->tokenHandler(array($this,'tokenHandler'));
    $this->provider->setParam('rest_call', NULL);  // Ignore the rest_call parameter
//    $this->provider->setParam('q', NULL);  // Ignore the q parameter
    $this->provider->setRequestTokenPath('/api/oauth/request_token');  // No token needed for this end point
    $this->provider->checkOAuthRequest();
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
  protected function lookupConsumer(\OAuthProvider $provider) {
    $entity_controller = entity_get_controller('restful_oauth_consumer');
    $consumer = $entity_controller->loadByConsumerKey($provider->consumer_key);
    if (empty($consumer)) {
      return OAUTH_CONSUMER_KEY_UNKNOWN;
    }
    if($provider->consumer_key != $consumer->consumer_key) {
      return OAUTH_CONSUMER_KEY_UNKNOWN;
    } else if($consumer->key_status != 0) {  // 0 is active, 1 is throttled, 2 is blacklisted
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
  protected function timestampNonceChecker(\OAuthProvider $provider) {

    if ($provider->nonce === 'bad') {
      return OAUTH_BAD_NONCE;
    } elseif ($provider->timestamp == '0') {
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
  protected function tokenHandler(\OAuthProvider $provider) {
    if ($provider->token === 'rejected') {
      return OAUTH_TOKEN_REJECTED;
    } elseif ($provider->token === 'revoked') {
      return OAUTH_TOKEN_REVOKED;
    }

    $provider->token_secret = "the_tokens_secret";
    return OAUTH_OK;
  }

}

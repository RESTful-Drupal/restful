<?php
/**
 * @file
 * Contains RestfulAuthenticationOAuth.
 */

class RestfulAuthenticationOAuth extends \RestfulAuthenticationBase {

  /**
   * Provider class.
   *
   * @var OAuthProvider
   */
  protected $provider = NULL;

  /**
   * {@inheritdoc}
   */
  public function applies($request = NULL) {
    // Only check requests with the 'authorization' header starting with OAuth.
    $auth_header = drupal_get_http_header('authorization');
    if ($auth_header) {
      return FALSE;
    }
    return preg_match('/^OAuth/', $auth_header);
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate($request = NULL) {
    try {
      // Initialize and configure the OauthProvider too handle the request.
      $this->provider = new OAuthProvider();
      $this->provider->consumerHandler(array($this,'lookupConsumer'));
      $this->provider->timestampNonceHandler(array($this,'timestampNonceChecker'));
      $this->provider->tokenHandler(array($this,'tokenHandler'));
      $this->provider->is2LeggedEndpoint(TRUE);

      // Now check the request validity.
      $this->provider->checkOAuthRequest();
    } catch (OAuthException $e) {
      // The OAuth extension throws an alert when there is something wrong
      // with the request (ie. the consumer key is invalid).
      watchdog('oauth', $e->getMessage(), array(), WATCHDOG_WARNING);
      return NULL;
    }

    // Check if we found a user.
    if (!empty($this->user)) {
      return $this->user;
    }
    return NULL;
  }
}

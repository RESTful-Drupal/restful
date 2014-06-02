<?php

/**
 * @file
 * Contains Cipher.
 */

class Cipher {

  private $securekey;

  function __construct() {
    $this->securekey = hash('sha256', drupal_get_hash_salt(), TRUE);
  }

  /**
   * Encrypt a string.
   *
   * @param $input
   * @return string
   */
  public function encrypt($input) {
    return drupal_hmac_base64($input, $this->securekey);
  }

  /**
   * Get nonce.
   *
   * @return string
   *   The nonce string.
   */
  public function getNonce() {
    $date = \DateTime::createFromFormat('U', REQUEST_TIME);
    return array('nonce' => $this->encrypt('First requested: ' . $date->format('d-m-Y')));
  }

  /**
   * Check nonce.
   *
   * @param string $nonce
   *   The nonce string.
   *
   * @return bool
   *   TRUE if the nonce is OK.
   */
  public function checkNonce($nonce) {
    $check = $this->getNonce();
    return $check['nonce'] == $nonce;
  }

  /**
   * Sign the request.
   *
   * @param $method
   *   One of OAUTH_SIG_METHOD_HMACSHA1, OAUTH_SIG_METHOD_RSASHA1 or 'PLAINTEXT'
   * @param array $request
   *   Array of param values to sign. Only include OAuth params.
   * @param string $consumer_key
   *   The consumer key.
   * @param string $token_secret
   *   The token secret (if any). Not used for request_token.
   *
   * @return string
   *   The signature for the request.
   */
  public function signRequest($method, $request, $consumer_key, $token_secret = '') {
    // Make sure the params are in alphabetic order.
    $key = $consumer_key . '&' . $token_secret;

    $consumer_controller = entity_get_controller('restful_oauth_consumer');
    $consumer = $consumer_controller->loadByConsumerKey($consumer_key);
    if ($method == 'PLAINTEXT') {
      return $key;
    }
    $oauth = new \OAuth($consumer_key, $consumer->consumer_secret, $method);
    ksort($request);
    $url = url(request_path(), array('absolute' => TRUE));
    return $oauth->generateSignature($_SERVER['REQUEST_METHOD'], $url, $request);
  }

}

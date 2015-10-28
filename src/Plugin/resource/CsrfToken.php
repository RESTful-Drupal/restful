<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\CsrfToken.
 */

namespace Drupal\restful\Plugin\resource;
use Drupal\restful\Resource\ResourceManager;

/**
 * Class CsrfToken
 * @package Drupal\restful\Plugin\resource
 *
 * @Resource(
 *   name = "csrf_token:1.0",
 *   resource = "csrf_token",
 *   label = "CSRF Token",
 *   description = "Resource that provides CSRF Tokens when using cookie authentication.",
 *   authenticationTypes = TRUE,
 *   authenticationOptional = TRUE,
 *   menuItem = "session/token",
 *   majorVersion = 1,
 *   minorVersion = 0
 * )
 */
class CsrfToken extends Resource implements ResourceInterface {

  /**
   * {@inheritdoc}
   */
  protected function publicFields() {
    return array(
      'X-CSRF-Token' => array(
        'callback' => '\Drupal\restful\Plugin\resource\CsrfToken::getCsrfToken',
      ),
    );
  }

  /**
   * Value callback; Return the CSRF token.
   *
   * @return array
   */
  public static function getCsrfToken() {
    return drupal_get_token(\Drupal\restful\Plugin\authentication\Authentication::TOKEN_VALUE);
  }

  /**
   * Overrides RestfulBase::access().
   *
   * Expose resource only to authenticated users.
   */
  public function access() {
    $account = $this->getAccount();
    return (bool) $account->uid && parent::access();
  }

  /**
   * {@inheritdoc}
   */
  public function index($path) {
    $values = array();
    foreach ($this->publicFields() as $public_property => $info) {
      $value = NULL;

      if ($info['callback']) {
        $value = ResourceManager::executeCallback($info['callback']);
      }

      if ($value && !empty($info['process_callbacks'])) {
        foreach ($info['process_callbacks'] as $process_callback) {
          $value = ResourceManager::executeCallback($process_callback, array($value));
        }
      }

      $values[$public_property] = $value;
    }

    return $values;
  }

}

<?php

/**
 * @file
 * Contains RestfulCsrfTokenResource.
 */

class RestfulCsrfTokenResource extends RestfulBase implements \RestfulDataProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function publicFieldsInfo() {
    return array(
      'X-CSRF-Token' => array(
        'callback' => 'static::getCsrfToken',
      ),
    );
  }

  /**
   * Value callback; Return the CSRF token.
   *
   * @return array
   */
  protected static function getCsrfToken() {
    return drupal_get_token(\RestfulInterface::TOKEN_VALUE);
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
  public function index() {
    foreach ($this->getPublicFields() as $public_property => $info) {
      $value = NULL;

      if ($info['callback']) {
        $value = static::executeCallback($info['callback']);
      }

      if ($value && $info['process_callbacks']) {
        foreach ($info['process_callbacks'] as $process_callback) {
          $value = static::executeCallback($process_callback, array($value));
        }
      }

      $values[$public_property] = $value;
    }

    return $values;
  }

}

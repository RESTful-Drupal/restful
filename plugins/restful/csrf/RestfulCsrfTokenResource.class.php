<?php

/**
 * @file
 * Contains RestfulCsrfTokenResource.
 */

class RestfulCsrfTokenResource extends RestfulBase {

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
    return (bool) $account->uid;
  }

}

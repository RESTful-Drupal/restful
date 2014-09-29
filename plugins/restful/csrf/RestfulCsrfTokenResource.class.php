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
    $public_fields = array();
    $public_fields['X-CSRF-Token'] = array(
      'callback' => array($this, 'getCsrfToken'),
    );
    return $public_fields;
  }

  /**
   * Value callback; Return the CSRF token.
   *
   * @return array
   */
  protected function getCsrfToken() {
    return drupal_get_token(\RestfulInterface::TOKEN_VALUE);
  }
}

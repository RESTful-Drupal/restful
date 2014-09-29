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
    $public_fields['csrf_token'] = array(
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
    return restful_csrf_session_token();
  }
}

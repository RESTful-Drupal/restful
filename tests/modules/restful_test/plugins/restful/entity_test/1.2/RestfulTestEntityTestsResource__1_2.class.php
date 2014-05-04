<?php

/**
 * @file
 * Contains RestfulTestEntityTestsResource__1_2.
 */

class RestfulTestEntityTestsResource__1_2 extends RestfulTestEntityTestsResource {

  /**
   * Overrides RestfulTestEntityTestsResource::getPublicFields().
   */
  public function getPublicFields() {
    $public_fields = parent::getPublicFields();

    $public_fields['callback'] = array(
      'callback' => array($this, 'callback'),
    );

    $public_fields['process_callback_from_callback'] = array(
      'callback' => array($this, 'callback'),
      'process_callback' => array($this, 'processCallbackFromCallback'),
    );

    $public_fields['process_callback_from_value'] = array(
      'wrapper_method' => 'getIdentifier',
      'wrapper_method_on_entity' => TRUE,
      'process_callback' => array($this, 'processCallbackFromValue'),
    );

    return $public_fields;
  }

  /**
   * Return a computed value.
   */
  protected function callback() {
    return 'callback';
  }

  /**
   * Process a computed value.
   */
  protected function processCallbackFromCallback($value) {
    return $value . ' processed from callback';
  }

  /**
   * Return a property value.
   */
  protected function processCallbackFromValue($value) {
    return $value . ' processed from value';
  }
}

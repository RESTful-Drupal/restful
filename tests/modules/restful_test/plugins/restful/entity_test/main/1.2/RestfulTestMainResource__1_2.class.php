<?php

/**
 * @file
 * Contains RestfulTestMainResource__1_2.
 */

class RestfulTestMainResource__1_2 extends RestfulTestMainResource {

  /**
   * Overrides RestfulTestEntityTestsResource::publicFieldsInfo().
   */
  public function publicFieldsInfo() {
    $public_fields = parent::publicFieldsInfo();

    $public_fields['callback'] = array(
      'callback' => array($this, 'callback'),
    );

    $public_fields['process_callback_from_callback'] = array(
      'callback' => array($this, 'callback'),
      'process_callbacks' => array(
        array($this, 'processCallbackFromCallback'),
      ),
    );

    $public_fields['process_callback_from_value'] = array(
      'wrapper_method' => 'getIdentifier',
      'wrapper_method_on_entity' => TRUE,
      'process_callbacks' => array(
        array($this, 'processCallbackFromValue'),
      ),
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
   * Process a property value.
   */
  protected function processCallbackFromValue($value) {
    return $value . ' processed from value';
  }
}

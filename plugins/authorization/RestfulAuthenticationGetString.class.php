<?php
/**
 * @file
 * Authorization plugin class.
 */

class RestfulAuthenticationGetString extends RestfulAuthorizationBase {
  /**
   * Overrides RestfulAuthenticationBase::authorize().
   */
  public function authorize() {
    // Get the name of the get parameter.
    if ($param_name = $this->{$this->settings['parameter name method']}()) {
      if (!empty($_GET[$param_name])) {
        return $this->{$this->settings['parameter value method']}() == $_GET[$param_name];
      }
    }
    return FALSE;
  }

  /**
   * Get the parameter name to check.
   */
  protected function getParamName() {
    return variable_get('restful_authentication_get_string_name', 'token');
  }

  /**
   * Get the parameter value to check.
   */
  protected function getParamValue() {
    $default = drupal_hmac_base64('restful_authentication_get_string_value', drupal_get_hash_salt());
    return variable_get('restful_authentication_get_string_value', $default);
  }

}

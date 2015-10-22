<?php

/**
 * @file
 * Contains \Drupal\restful\Exception\RestfulException.
 */

namespace Drupal\restful\Exception;

use Drupal\restful\Http\Response;

class RestfulException extends \Exception {

  /**
   * Defines the instance resource.
   *
   * @var string
   */
  protected $instance = NULL;

  /**
   * Array keyed by the field name, and array of error messages as value.
   *
   * @var array
   */
  protected $fieldErrors = array();

  /**
   * Array of extra headers to set when throwing an exception.
   *
   * @var array
   */
  protected $headers = array();

  /**
   * Exception description.
   *
   * @var string
   */
  protected $description = '';

  /**
   * Gets the description of the exception.
   *
   * @return string
   *   The description.
   */
  final public function getDescription() {
    return $this->description ? $this->description : Response::$statusTexts[$this->getCode()];
  }

  /**
   * Return a string to the common problem type.
   *
   * @return string
   *   URL pointing to the specific RFC-2616 section.
   */
  public function getType() {
    // Depending on the error code we'll return a different URL.
    $url = 'http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html';
    $sections = array(
      '100' => '#sec10.1.1',
      '101' => '#sec10.1.2',
      '200' => '#sec10.2.1',
      '201' => '#sec10.2.2',
      '202' => '#sec10.2.3',
      '203' => '#sec10.2.4',
      '204' => '#sec10.2.5',
      '205' => '#sec10.2.6',
      '206' => '#sec10.2.7',
      '300' => '#sec10.3.1',
      '301' => '#sec10.3.2',
      '302' => '#sec10.3.3',
      '303' => '#sec10.3.4',
      '304' => '#sec10.3.5',
      '305' => '#sec10.3.6',
      '307' => '#sec10.3.8',
      '400' => '#sec10.4.1',
      '401' => '#sec10.4.2',
      '402' => '#sec10.4.3',
      '403' => '#sec10.4.4',
      '404' => '#sec10.4.5',
      '405' => '#sec10.4.6',
      '406' => '#sec10.4.7',
      '407' => '#sec10.4.8',
      '408' => '#sec10.4.9',
      '409' => '#sec10.4.10',
      '410' => '#sec10.4.11',
      '411' => '#sec10.4.12',
      '412' => '#sec10.4.13',
      '413' => '#sec10.4.14',
      '414' => '#sec10.4.15',
      '415' => '#sec10.4.16',
      '416' => '#sec10.4.17',
      '417' => '#sec10.4.18',
      '500' => '#sec10.5.1',
      '501' => '#sec10.5.2',
      '502' => '#sec10.5.3',
      '503' => '#sec10.5.4',
      '504' => '#sec10.5.5',
      '505' => '#sec10.5.6',
    );
    return empty($sections[$this->getCode()]) ? $url : $url . $sections[$this->getCode()];
  }

  /**
   * Get the URL to the error for the particular case.
   *
   * @return string
   *   The url or NULL if empty.
   */
  public function getInstance() {
    // Handle all instances using the advanced help module.
    if (!module_exists('advanced_help') || empty($this->instance)) {
      return NULL;
    }
    return url($this->instance, array(
      'absolute' => TRUE,
    ));
  }

  /**
   * Return an array with all the errors.
   */
  public function getFieldErrors() {
    return $this->fieldErrors;
  }

  /**
   * Add an error per field.
   *
   * @param string $field_name
   *   The field name.
   * @param string $error_message
   *   The error message.
   */
  public function addFieldError($field_name, $error_message) {
    $this->fieldErrors[$field_name][] = $error_message;
  }

  /**
   * Get the associative array of headers.
   *
   * @return array
   *   The associated headers to the error exception.
   */
  public function getHeaders() {
    return $this->headers;
  }

  /**
   * Set a header.
   *
   * @param string $key
   *   The header name.
   * @param string $value
   *   The header value.
   */
  public function setHeader($key, $value) {
    $this->headers[$key] = $value;
  }

}

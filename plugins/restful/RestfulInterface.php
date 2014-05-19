<?php


/**
 * @file
 * Contains RestfulInterface.
 */

interface RestfulInterface {

  /**
   * Constructor for the RESTful handler.
   *
   * @param $plugin
   *   The restful plugin object.
   */
  public function __construct($plugin);

  /**
   * Entry point to process a request.
   *
   * @param string $path
   *   The requested path.
   * @param array $request
   *   The request array
   * @param stdClass $account
   *   The user object.
   * @param string $method
   *   The HTTP verb.
   *
   * @return
   *   TRUE or FALSE based on delivery status.
   */
  public function process($path = '', $request = NULL, $account = NULL, $method = 'get');

  /**
   * Return the properties that should be public.
   *
   * @return array
   */
  public function getPublicFields();

  /**
   * Return array keyed by the header property, and the value.
   *
   * This can be used for example to change the "Status" code of the HTTP
   * response, or to add a "Location" property.
   *
   * @return array
   */
  public function getHttpHeaders();

  /**
   * Determine if user can access the handler.
   *
   * @return bool
   *   TRUE if the current request has access to the requested resource. FALSE
   *   otherwise.
   */
  public function access();
}

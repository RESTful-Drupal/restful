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
   */
  public function access();
}

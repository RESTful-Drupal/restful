<?php


/**
 * @file
 * Contains RestfulInterface.
 */

interface RestfulInterface {

  /**
   * HTTP methods.
   */
  const GET = 'GET';
  const PUT = 'PUT';
  const POST = 'POST';
  const PATCH = 'PATCH';
  const OPTIONS = 'OPTIONS';
  const HEAD = 'HEAD';
  const TRACE = 'TRACE';
  const DELETE = 'DELETE';
  const CONNECT = 'CONNECT';

  /**
   * Token value for token generation functions.
   */
  const TOKEN_VALUE = 'rest';

  /**
   * Constructor for the RESTful handler.
   *
   * @param $plugin
   *   The restful plugin object.
   * @param RestfulAuthenticationManager $auth_manager
   *   Injected authentication manager.
   * @param DrupalCacheInterface $cache_controller
   *   Injected cache controller.
   */
  public function __construct($plugin, \RestfulAuthenticationManager $auth_manager = NULL, \DrupalCacheInterface $cache_controller = NULL);

  /**
   * Entry point to process a request.
   *
   * @param string $path
   *   The requested path.
   * @param array $request
   *   The request array.
   * @param string $method
   *   The HTTP method.
   * @param bool $check_rate_limit
   *   Determines if rate limit should be checked. This could be set to FALSE
   *   for example when inside a recursion, and we don't want to cout multiple
   *   times the same request. Defautls to TRUE.
   *
   * @return mixed
   *   The return value can depend on the controller for the current $method.
   */
  public function process($path = '', array $request = array(), $method = \RestfulInterface::GET, $check_rate_limit = TRUE);


  /**
   * Return the properties that should be public.
   *
   * @return array
   */
  public function publicFieldsInfo();

  /**
   * Return the properties that should be public after processing.
   *
   * Default values would be assigned to the properties declared in
   * \RestfulInterface::publicFieldsInfo().
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

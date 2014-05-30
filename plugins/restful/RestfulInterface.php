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
   *   The request array
   * @param string $method
   *   The HTTP method.
   *
   * @return mixed
   *   The return value can depend on the controller for the current $method.
   */
  public function process($path = '', $request = NULL, $method = 'get');

  /**
   * Return the properties that should be public.
   *
   * @return array
   */
  public function getPublicFields();

  /**
   * Determine if user can access the handler.
   *
   * @return bool
   *   TRUE if the current request has access to the requested resource. FALSE
   *   otherwise.
   */
  public function access();
}

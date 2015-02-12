<?php

/**
 * @file
 * Contains \Drupal\restful\Http\RequestInterface.
 */

namespace Drupal\restful\Http;

interface RequestInterface {

  /**
   * Creates a new request with values from PHP's super globals.
   *
   * @return RequestInterface
   *   Request A Request instance
   */
  public static function createFromGlobals();

  /**
   * Creates a Request based on a given URI and configuration.
   *
   * TODO: Add documentation.
   *
   * @return RequestInterface
   *   Request A Request instance
   */
  public static function create($path, $query, $method = 'GET', HttpHeaderBag $headers, $viaRouter = FALSE, $csrfToken = NULL, $cookies = array(), $files = array(), $server = array());

  /**
   * Determines if the HTTP method represents a write operation.
   *
   * @param string $method
   *   The method name.
   *
   * @return boolean
   *   TRUE if it is a write operation. FALSE otherwise.
   */
  public static function isWriteMethod($method);

  /**
   * Determines if the HTTP method represents a read operation.
   *
   * @param string $method
   *   The method name.
   *
   * @return boolean
   *   TRUE if it is a read operation. FALSE otherwise.
   */
  public static function isReadMethod($method);

  /**
   * Determines if the HTTP method is one of the known methods.
   *
   * @param string $method
   *   The method name.
   *
   * @return boolean
   *   TRUE if it is a known method. FALSE otherwise.
   */
  public static function isValidMethod($method);

  /**
   * Helper method to know if the current request is for a list.
   *
   * @return boolean
   *   TRUE if the request is for a list. FALSE otherwise.
   */
  public function isListRequest();

  /**
   * Parses the body string.
   *
   * @return array
   */
  public function getParsedBody();

  /**
   * Parses the input data provided via URL params.
   *
   * @return array
   */
  public function getParsedInput();

  /**
   * Gets the request path.
   *
   * @return string
   */
  public function getPath();

  /**
   * Gets the fully qualified URL with the query params.
   *
   * @return string
   *   The URL.
   */
  public function href();

  /**
   * Gets the headers bag.
   *
   * @return HttpHeaderBag
   */
  public function getHeaders();

  /**
   * Returns the user.
   *
   * @return
   *   string|null
   */
  public function getUser();

  /**
   * Returns the password.
   *
   * @return
   *   string|null
   */
  public function getPassword();

  /**
   * Get the HTTP method.
   *
   * @return string
   */
  public function getMethod();

  /**
   * Get the server information.
   *
   * @return array
   */
  public function getServer();

  /**
   * Sets an object in the application data store.
   *
   * @param string $key
   *   Identifier.
   * @param mixed $value
   *   The data to store as part of the request.
   */
  public function setApplicationData($key, $value);

  /**
   * Gets an object from the application data store.
   *
   * @param string $key
   *   Identifier.
   *
   * @return mixed
   *   The data stored as part of the request.
   */
  public function getApplicationData($key);

  /**
   * Checks whether the request is secure or not.
   *
   * This method can read the client port from the "X-Forwarded-Proto" header
   * when trusted proxies were set via "setTrustedProxies()".
   *
   * The "X-Forwarded-Proto" header must contain the protocol: "https" or "http".
   *
   * If your reverse proxy uses a different header name than "X-Forwarded-Proto"
   * ("SSL_HTTPS" for instance), configure it via "setTrustedHeaderName()" with
   * the "client-proto" key.
   *
   * @return bool
   */
  public function isSecure();

}

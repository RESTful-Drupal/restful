<?php

/**
 * @file
 * Contains \Drupal\restful\Http\RequestInterface.
 */

namespace Drupal\restful\Http;

interface RequestInterface {

  const HEADER_CLIENT_IP = 'client_ip';
  const HEADER_CLIENT_HOST = 'client_host';
  const HEADER_CLIENT_PROTO = 'client_proto';
  const HEADER_CLIENT_PORT = 'client_port';

  const METHOD_HEAD = 'HEAD';
  const METHOD_GET = 'GET';
  const METHOD_POST = 'POST';
  const METHOD_PUT = 'PUT';
  const METHOD_PATCH = 'PATCH';
  const METHOD_DELETE = 'DELETE';
  const METHOD_PURGE = 'PURGE';
  const METHOD_OPTIONS = 'OPTIONS';
  const METHOD_TRACE = 'TRACE';
  const METHOD_CONNECT = 'CONNECT';

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
   * @param string $path
   *   The requested path.
   * @param array $query
   *   The query string parameters being passed.
   * @param string $method
   *   A valid HTTP method
   * @param HttpHeaderBag $headers
   *   The headers for the request
   * @param bool $via_router
   *   Boolean indicating that if the requested was created via the Drupal's
   *   menu router.
   * @param string $csrf_token
   *   A CSRF token that applies to the current request.
   * @param array $cookies
   *   An array of key value pairs containing information about the cookies.
   * @param array $files
   *   An array of key value pairs containing information about the files.
   * @param array $server
   *   An array of key value pairs containing information about the server.
   *
   * @return RequestInterface Request
   *   A Request instance
   */
  public static function create($path, array $query = array(), $method = 'GET', HttpHeaderBag $headers = NULL, $via_router = FALSE, $csrf_token = NULL, array $cookies = array(), array $files = array(), array $server = array());

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
   * @param string $resource_path
   *   The resource path without any prefixes.
   *
   * @return boolean
   *   TRUE if the request is for a list. FALSE otherwise.
   */
  public function isListRequest($resource_path);

  /**
   * Parses the body string.
   *
   * @return array
   *   The parsed body.
   */
  public function getParsedBody();

  /**
   * Parses the input data provided via URL params.
   *
   * @return array
   *   The parsed input.
   */
  public function getParsedInput();

  /**
   * Parses the input data provided via URL params.
   *
   * @param array $input
   *   The input to set.
   */
  public function setParsedInput(array $input);

  /**
   * Gets the request path.
   *
   * @param bool $strip
   *   If TRUE it will strip the restful API prefix from the beginning.
   *
   * @return string
   *   The path.
   */
  public function getPath($strip = TRUE);

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
   * Set the HTTP method.
   *
   * @param string $method
   *   The method to set.
   */
  public function setMethod($method);

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
   * Resets the application data.
   */
  public function clearApplicationData();

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
   * The "X-Forwarded-Proto" header must contain the protocol: "https" or
   * "http".
   *
   * If your reverse proxy uses a different header name than "X-Forwarded-Proto"
   * ("SSL_HTTPS" for instance), configure it via "setTrustedHeaderName()" with
   * the "client-proto" key.
   *
   * @return bool
   */
  public function isSecure();

  /**
   * Get the files in the request.
   *
   * @return array
   *   The available files in the request.
   */
  public function getFiles();

  /**
   * Get CSRF Token.
   *
   * @return string
   *   Gets the CSRF token.
   */
  public function getCsrfToken();

  /**
   * Is via router.
   *
   * @return bool
   *   TRUE if the request was generated via the Drupal's menu router.
   */
  public function isViaRouter();

  /**
   * Marks the request as being via Drupal's menu router.
   *
   * @param bool $via_router
   *   The flag to set.
   */
  public function setViaRouter($via_router);

  /**
   * @return array
   */
  public function getCookies();

  /**
   * Get the normalized pager input.
   *
   * This is to support page=1&range=6 and page[number]=1&page[size]=6 at the
   * same time.
   *
   * @return array
   *   An associative array with the pager information in the form of
   *   page[number]=1&page[size]=6.
   */
  public function getPagerInput();

}

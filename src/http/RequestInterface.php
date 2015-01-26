<?php

/**
 * Contains \Drupal\restful\http\RequestInterface.
 */

namespace Drupal\restful\http;

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
   * @param boolean $strict
   *   TRUE if the comparisons are case sensitive.
   *
   * @return boolean
   *   TRUE if it is a write operation. FALSE otherwise.
   */
  public static function isWriteMethod($method, $strict = TRUE);

  /**
   * Determines if the HTTP method represents a read operation.
   *
   * @param string $method
   *   The method name.
   * @param boolean $strict
   *   TRUE if the comparisons are case sensitive.
   *
   * @return boolean
   *   TRUE if it is a read operation. FALSE otherwise.
   */
  public static function isReadMethod($method, $strict = TRUE);

  /**
   * Determines if the HTTP method is one of the known methods.
   *
   * @param string $method
   *   The method name.
   * @param boolean $strict
   *   TRUE if the comparisons are case sensitive.
   *
   * @return boolean
   *   TRUE if it is a known method. FALSE otherwise.
   */
  public static function isValidMethod($method, $strict = TRUE);

//  /**
//   * Normalizes a query string.
//   *
//   * It builds a normalized query string, where keys/value pairs are alphabetized,
//   * have consistent escaping and unneeded delimiters are removed.
//   *
//   * @param string $qs
//   *   Query string
//   *
//   * @return string
//   *   A normalized query string for the Request
//   */
//  public static function normalizeQueryString($qs);
//
//  /**
//   * Enables support for the _method request parameter to determine the intended HTTP method.
//   *
//   * Be warned that enabling this feature might lead to CSRF issues in your code.
//   * Check that you are using CSRF tokens when required.
//   * If the HTTP method parameter override is enabled, an html-form with method "POST" can be altered
//   * and used to send a "PUT" or "DELETE" request via the _method request parameter.
//   * If these methods are not protected against CSRF, this presents a possible vulnerability.
//   *
//   * The HTTP method can only be overridden when the real HTTP method is POST.
//   */
//  public static function enableHttpMethodParameterOverride();
//
//  /**
//   * Checks whether support for the _method request parameter is enabled.
//   *
//   * @return bool
//   *   True when the _method request parameter is enabled, false otherwise.
//   */
//  public static function getHttpMethodParameterOverride();
//
//  /**
//   * Returns the client IP addresses.
//   *
//   * In the returned array the most trusted IP address is first, and the
//   * least trusted one last. The "real" client IP address is the last one,
//   * but this is also the least trusted one. Trusted proxies are stripped.
//   *
//   * Use this method carefully; you should use getClientIp() instead.
//   *
//   * @return array
//   *   The client IP addresses
//   *
//   * @see getClientIp()
//   */
//  public function getClientIps();
//
//  /**
//   * Returns the client IP address.
//   *
//   * This method can read the client IP address from the "X-Forwarded-For" header
//   * when trusted proxies were set via "setTrustedProxies()". The "X-Forwarded-For"
//   * header value is a comma+space separated list of IP addresses, the left-most
//   * being the original client, and each successive proxy that passed the request
//   * adding the IP address where it received the request from.
//   *
//   * If your reverse proxy uses a different header name than "X-Forwarded-For",
//   * ("Client-Ip" for instance), configure it via "setTrustedHeaderName()" with
//   * the "client-ip" key.
//   *
//   * @return string
//   *   The client IP address
//   *
//   * @see getClientIps()
//   * @see http://en.wikipedia.org/wiki/X-Forwarded-For
//   */
//  public function getClientIp();
//
//  /**
//   * Gets the request's scheme.
//   *
//   * @return string
//   *
//   * @api
//   */
//  public function getScheme();

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

//  /**
//   * Returns the HTTP host being requested.
//   *
//   * The port name will be appended to the host if it's non-standard.
//   *
//   * @return string
//   *
//   * @api
//   */
//  public function getHttpHost();
//
//  /**
//   * Generates the normalized query string for the Request.
//   *
//   * It builds a normalized query string, where keys/value pairs are alphabetized
//   * and have consistent escaping.
//   *
//   * @return string|null A normalized query string for the Request
//   *
//   * @api
//   */
//  public function getQueryString();
//
}

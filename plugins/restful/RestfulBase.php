<?php

/**
 * @file
 * Contains RestfulBase.
 */

abstract class RestfulBase implements RestfulInterface {

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
  public static function isWriteMethod($method, $strict = TRUE) {
    $method = $strict ? $method : strtoupper($method);
    return in_array($method, array(
      \RestfulInterface::PUT,
      \RestfulInterface::POST,
      \RestfulInterface::PATCH,
      \RestfulInterface::DELETE,
    ));
  }

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
  public static function isReadMethod($method, $strict = TRUE) {
    $method = $strict ? $method : strtoupper($method);
    return in_array($method, array(
      \RestfulInterface::GET,
      \RestfulInterface::HEAD,
      \RestfulInterface::OPTIONS,
      \RestfulInterface::TRACE,
      \RestfulInterface::CONNECT,
    ));
  }

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
  public static function isKnownMethod($method, $strict = TRUE) {
    $method = $strict ? $method : strtolower($method);
    return static::isReadMethod($method, $strict) || static::isWriteMethod($method, $strict);
  }
}

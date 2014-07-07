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
   *
   * @return boolean
   *   TRUE if it is a write operation. FALSE otherwise.
   */
  public static function isWriteMethod($method) {
    return !static::isReadMethod($method);
  }

  /**
   * Determines if the HTTP method represents a read operation.
   *
   * @param string $method
   *   The method name.
   *
   * @return boolean
   *   TRUE if it is a read operation. FALSE otherwise.
   */
  public static function isReadMethod($method) {
    return in_array($method, array(
      \RestfulInterface::GET,
      \RestfulInterface::HEAD,
      \RestfulInterface::OPTIONS,
      \RestfulInterface::TRACE,
      \RestfulInterface::CONNECT,
    ));
  }

}

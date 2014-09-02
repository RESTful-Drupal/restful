<?php

/**
 * @file
 * Contains RestfulBase.
 */

abstract class RestfulBase implements RestfulInterface {

  /**
   * The plugin definition.
   *
   * @var array $plugin
   */
  protected $plugin;

  /**
   * Constructs a RestfulEntityBase object.
   *
   * @param array $plugin
   *   Plugin definition.
   * @param RestfulAuthenticationManager $auth_manager
   *   (optional) Injected authentication manager.
   * @param DrupalCacheInterface $cache_controller
   *   (optional) Injected cache backend.
   */
  public function __construct($plugin, \RestfulAuthenticationManager $auth_manager = NULL, \DrupalCacheInterface $cache_controller = NULL) {
    $this->plugin = $plugin;
  }

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
  public static function isValidMethod($method, $strict = TRUE) {
    $method = $strict ? $method : strtolower($method);
    return static::isReadMethod($method, $strict) || static::isWriteMethod($method, $strict);
  }

  /**
   * Gets information about the restful plugin.
   *
   * @param string
   *   (optional) The name of the key to return.
   *
   * @return mixed
   *   Depends on the requested value.
   */
  public function getPluginInfo($key = NULL) {
    if (isset($key)) {
      return empty($this->plugin[$key]) ? NULL : $this->plugin[$key];
    }
    return $this->plugin;
  }

  /**
   * Call the output format on the given data.
   *
   * @param array $data
   *   The array of data to format.
   *
   * @return string
   *   The formatted output.
   */
  public function format(array $data) {
    $formatter_handler = restful_get_formatter_handler($this->getPluginInfo('formatter'), $this);
    return $formatter_handler->format($data);
  }
}

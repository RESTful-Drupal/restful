<?php

/**
 * @file
 * Contains \Drupal\restful\Http\HttpHeaderInterface
 */

namespace Drupal\restful\Http;

interface HttpHeaderInterface {

  /**
   * Creates a header object from the key and value strings.
   *
   * @param string $key
   *   The header name.
   * @param string $value
   *   The header value.
   *
   * @return HttpHeaderInterface
   *   The parsed header.
   */
  public static function create($key, $value);

  /**
   * Gets the values of the header.
   *
   * @return array
   *   The values for this header.
   */
  public function get();

  /**
   * Gets the contents of the header.
   *
   * @return string
   *   The header value as a string.
   */
  public function getValueString();

  /**
   * Gets the header name.
   *
   * @return string
   */
  public function getName();

  /**
   * Sets the values.
   *
   * @param array $values
   *   A numeric array containing all the values for the given header.
   */
  public function set($values);

  /**
   * Appends a value into a header.
   *
   * @param string $value
   *   The string value to append.
   */
  public function append($value);

  /**
   * Gets the header id.
   *
   * @return string
   *   The header ID.
   */
  public function getId();

  /**
   * Generates the header ID based on the header name.
   *
   * @param string $name
   *   The header name.
   *
   * @return string
   *   The ID.
   */
  public static function generateId($name);

}

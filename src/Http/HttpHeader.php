<?php

/**
 * @file
 * Contains \Drupal\restful\Http\HttpHeader
 */

namespace Drupal\restful\Http;

class HttpHeader implements HttpHeaderInterface {

  /**
   * Header ID.
   *
   * @var string
   */
  protected $id;

  /**
   * Header name.
   *
   * @var string
   */
  protected $name;

  /**
   * Header values.
   *
   * @var array
   */
  protected $values = array();

  /**
   * Header extras.
   *
   * @var string
   */
  protected $extras;

  /**
   * Constructor.
   */
  public function __construct($name, array $values, $extras) {
    $this->name = $name;
    $this->id = static::generateId($name);
    $this->values = $values;
    $this->extras = $extras;
  }

  /**
   * {@inheritdoc}
   */
  public static function create($key, $value) {
    list($extras, $values) = self::parseHeaderValue($value);
    return new static($key, $values, $extras);
  }

  /**
   * {@inheritdoc}
   */
  public function get() {
    return $this->values;
  }

  /**
   * {@inheritdoc}
   */
  public function getValueString() {
    $parts = array();
    $parts[] = implode(', ', $this->values);
    $parts[] = $this->extras;
    return implode('; ', array_filter($parts));
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Returns the string version of the header.
   *
   * @return string
   */
  public function __toString() {
    return $this->name . ': ' . $this->getValueString();
  }

  /**
   * {@inheritdoc}
   */
  public function set($values) {
    $this->values = $values;
  }

  /**
   * {@inheritdoc}
   */
  public function append($value) {
    // Ignore the extras.
    list(, $values) = static::parseHeaderValue($value);
    foreach ($values as $value) {
      $this->values[] = $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateId($name) {
    return strtolower($name);
  }

  /**
   * Parses the values and extras from a header value string.
   *
   * @param string $value
   *
   * @return array
   *   The $extras and $values.
   */
  protected static function parseHeaderValue($value) {
    $extras = NULL;
    $parts = explode(';', $value);
    if (count($parts) > 1) {
      // Only consider the last element.
      $extras = array_pop($parts);
      $extras = trim($extras);
      // In case there were more than one ';' then put everything back.
      $value = implode(';', $parts);
    }
    $values = array_map('trim', explode(',', $value));
    return array($extras, $values);
  }

}

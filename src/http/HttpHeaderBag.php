<?php

/**
 * @file
 * Contains \Drupal\restful\Http\HttpHeaderBag.
 */

namespace Drupal\restful\Http;

use \Drupal\restful\Exception\ServerConfigurationException;

class HttpHeaderBag implements HttpHeaderBagInterface {

  /**
   * The header objects keyed by ID.
   *
   * @var array
   */
  protected $values = array();

  /**
   * Constructor
   *
   * @param array $headers
   *   Array of key value pairs.
   */
  public function __construct($headers = array()) {
    foreach ($headers as $key => $value) {
      $header = HttpHeader::create($key, $value);
      $this->values[$header->getId()] = $header;
    }
  }

  /**
   * Returns the header bag as a string.
   *
   * @return string
   *   The string representation.
   */
  public function __toString() {
    $headers = array();
    foreach ($this->values as $key => $header) {
      /** @var HttpHeader $header */
      $headers[] = $header->__toString();
    }
    return implode("\r\n", $headers);
  }

  /**
   * {@inheritdoc}
   */
  public function get($key) {
    // Assume that $key is an ID.
    if (array_key_exists($key, $this->values)) {
      return $this->values[$key];
    }
    // Test if key was a header name.
    $key = HttpHeader::generateId($key);
    if (array_key_exists($key, $this->values)) {
      return $this->values[$key];
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function has($key) {
    return !!$this->get($key);
  }

  /**
   * {@inheritdoc}
   */
  public function getValues() {
    return $this->values;
  }

  /**
   * {@inheritdoc}
   */
  public function add(HttpHeaderInterface $header) {
    $this->values[$header->getId()] = $header;
  }

  /**
   * {@inheritdoc}
   */
  public function append(HttpHeaderInterface $header) {
    if (!$this->has($header->getId())) {
      $this->add($header);
      return;
    }
    $existing_header = $this->get($header->getId());
    // Append all the values in the passed in header to the existing header
    // values.
    foreach ($header->get() as $value) {
      $existing_header->append($value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function remove($key) {
    // Assume that $key is an ID.
    if (!array_key_exists($key, $this->values)) {
      // Test if key was a header name.
      $key = HttpHeader::generateId($key);
      if (!array_key_exists($key, $this->values)) {
        return;
      }
    }
    unset($this->values[$key]);
  }

}

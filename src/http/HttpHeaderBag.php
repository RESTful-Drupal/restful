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
   * Get the the header object for a header name or ID.
   *
   * @param string $key
   *   The header ID or header name.
   *
   * @return HttpHeaderInterface
   *   The header object.
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
   * Returns all the headers set on the bag.
   *
   * @return array
   */
  public function getValues() {
    return $this->values;
  }

  /**
   * Add a header to the bag.
   *
   * @param HttpHeaderInterface $header
   *   The header object.
   *
   * @throws ServerConfigurationException
   */
  public function add(HttpHeaderInterface $header) {
    $this->values[$header->getId()] = $header;
  }

}

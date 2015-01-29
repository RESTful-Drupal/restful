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

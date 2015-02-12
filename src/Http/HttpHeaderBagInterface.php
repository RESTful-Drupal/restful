<?php

/**
 * @file
 * Contains \Drupal\restful\Http\HttpHeaderBagInterface.
 */

namespace Drupal\restful\Http;

use \Drupal\restful\Exception\ServerConfigurationException;

interface HttpHeaderBagInterface {

  /**
   * Get the the header object for a header name or ID.
   *
   * @param string $key
   *   The header ID or header name.
   *
   * @return HttpHeaderInterface
   *   The header object.
   */
  public function get($key);

  /**
   * Checks the existence of a header in the bag.
   *
   * @param string $key
   *   The header ID or header name.
   *
   * @return bool
   *   TRUE if the header is present. FALSE otherwise.
   */
  public function has($key);

  /**
   * Returns all the headers set on the bag.
   *
   * @return array
   */
  public function getValues();

  /**
   * Add a header to the bag.
   *
   * @param HttpHeaderInterface $header
   *   The header object or an associative array with the name and value.
   *
   * @throws ServerConfigurationException
   */
  public function add(HttpHeaderInterface $header);

  /**
   * Appends the values of the passed in header to if the header already exists.
   *
   * @param HttpHeaderInterface $header
   *   The header object or an associative array with the name and value.
   *
   * @throws ServerConfigurationException
   */
  public function append(HttpHeaderInterface $header);

  /**
   * Removes a header from the bag.
   *
   * @param string $key
   *   The header ID or the header name.
   */
  public function remove($key);

}

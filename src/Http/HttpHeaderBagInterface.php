<?php

/**
 * @file
 * Contains \Drupal\restful\Http\HttpHeaderBagInterface.
 */

namespace Drupal\restful\Http;

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
   * @throws \RestfulServerConfigurationException
   */
  public function add(HttpHeaderInterface $header);

}

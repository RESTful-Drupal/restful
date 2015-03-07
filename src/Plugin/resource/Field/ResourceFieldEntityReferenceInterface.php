<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Field\ResourceFieldEntityReferenceInterface.
 */

namespace Drupal\restful\Plugin\resource\Field;

interface ResourceFieldEntityReferenceInterface extends ResourceFieldEntityInterface {

  /**
   * Creates a request object for the sub-request.
   *
   * @param array $value
   *   An associative array containing the values to set in the nested call, and
   *   information about how to create the request object.
   *
   * @return RequestInterface
   *   The request object.
   */
  public static function subRequest(array $value);

}

<?php

/**
 * @file
 * Contains \Drupal\restful\Exception\UnsupportedMediaTypeException.
 */

namespace Drupal\restful\Exception;

class UnsupportedMediaTypeException extends RestfulException {

  /**
   * Defines the HTTP error code.
   *
   * @var int
   */
  protected $code = 415;

  /**
   * Defines the description.
   *
   * @var string
   */
  protected $description = 'Unsupported Media Type.';

  /**
   * Defines the problem instance.
   *
   * @var string
   */
  protected $instance = 'help/restful/problem-instances-unsupported-media-type';

}

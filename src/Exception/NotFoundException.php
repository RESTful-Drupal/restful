<?php

/**
 * @file
 * Contains \Drupal\restful\Exception\NotFoundException.
 */

namespace Drupal\restful\Exception;

class NotFoundException extends RestfulException {

  /**
   * Defines the HTTP error code.
   *
   * @var int
   */
  protected $code = 404;

  /**
   * Defines the problem instance.
   *
   * @var string
   */
  protected $instance = 'help/restful/problem-instances-not-found';

}

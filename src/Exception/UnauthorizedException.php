<?php

/**
 * @file
 * Contains \Drupal\restful\Exception\UnauthorizedException.
 */

namespace Drupal\restful\Exception;

class UnauthorizedException extends RestfulException {

  /**
   * Defines the HTTP error code.
   *
   * @var int
   */
  protected $code = 401;

  /**
   * Defines the problem instance.
   *
   * @var string
   */
  protected $instance = 'help/restful/problem-instances-unauthorized';

}

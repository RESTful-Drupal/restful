<?php

/**
 * @file
 * Contains \Drupal\restful\Exception\ForbiddenException.
 */

namespace Drupal\restful\Exception;

class ForbiddenException extends RestfulException {

  /**
   * Defines the HTTP error code.
   *
   * @var int
   */
  protected $code = 403;

  /**
   * Defines the problem instance.
   *
   * @var string
   */
  protected $instance = 'help/restful/problem-instances-forbidden';

}

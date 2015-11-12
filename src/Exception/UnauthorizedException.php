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
   * {@inheritdoc}
   */
  protected $instance = 'help/restful/problem-instances-unauthorized';

}

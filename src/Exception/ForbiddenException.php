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
   * {@inheritdoc}
   */
  protected $instance = 'help/restful/problem-instances-forbidden';

}

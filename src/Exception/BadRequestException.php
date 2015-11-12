<?php

/**
 * @file
 * Contains \Drupal\restful\Exception\BadRequestException.
 */

namespace Drupal\restful\Exception;

class BadRequestException extends RestfulException {

  /**
   * Defines the HTTP error code.
   *
   * @var int
   */
  protected $code = 400;

  /**
   * {@inheritdoc}
   */
  protected $instance = 'help/restful/problem-instances-bad-request';

}

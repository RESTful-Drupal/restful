<?php

/**
 * @file
 * Contains \Drupal\restful\Exception\ServiceUnavailable.
 */

namespace Drupal\restful\Exception;

class ServiceUnavailableException extends RestfulException {

  /**
   * Defines the HTTP error code.
   *
   * @var int
   */
  protected $code = 503;

  /**
   * Defines the problem instance.
   *
   * @var string
   */
  protected $instance = 'help/restful/problem-instances-bad-request';

}

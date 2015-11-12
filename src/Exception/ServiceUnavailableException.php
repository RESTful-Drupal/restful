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
   * {@inheritdoc}
   */
  protected $instance = 'help/restful/problem-instances-bad-request';

}

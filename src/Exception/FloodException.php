<?php

/**
 * @file
 * Contains \Drupal\restful\Exception\FloodException.
 */

namespace Drupal\restful\Exception;

class FloodException extends RestfulException {

  /**
   * Defines the HTTP error code.
   *
   * @var int
   */
  protected $code = 429;

  /**
   * {@inheritdoc}
   */
  protected $instance = 'help/restful/problem-instances-flood';

}

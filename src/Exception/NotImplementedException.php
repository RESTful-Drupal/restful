<?php

/**
 * @file
 * Contains \Drupal\restful\Exception\NotImplementedException.
 */

namespace Drupal\restful\Exception;

class NotImplementedException extends RestfulException {

  /**
   * Defines the HTTP error code.
   *
   * @var int
   */
  protected $code = 501;

  /**
   * {@inheritdoc}
   */
  protected $instance = 'help/restful/problem-instances-not-implemented';

}

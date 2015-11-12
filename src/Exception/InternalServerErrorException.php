<?php

/**
 * @file
 * Contains \Drupal\restful\Exception\InternalServerErrorException
 */

namespace Drupal\restful\Exception;

class InternalServerErrorException extends RestfulException {


  /**
   * Defines the HTTP error code.
   *
   * @var int
   */
  protected $code = 500;

  /**
   * {@inheritdoc}
   */
  protected $instance = 'help/restful/problem-instances-server-configuration';

}

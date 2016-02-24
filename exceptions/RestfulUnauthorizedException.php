<?php

/**
 * @file
 * Contains RestfulUnauthorizedException.
 */

class RestfulUnauthorizedException extends \RestfulException {

  /**
   * Defines the HTTP error code.
   *
   * @var int
   */
  protected $code = 401;

  /**
   * {@inheritdoc}
   */
  protected $description = 'Unauthorized.';

  /**
   * {@inheritdoc}
   */
  protected $instance = 'help/restful/problem-instances-unauthorized';

}

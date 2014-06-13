<?php

/**
 * @file
 * Contains RestfulServiceUnavailable
 */

class RestfulServiceUnavailable extends RestfulException {

  /**
   * Defines the HTTP error code.
   *
   * @var int
   */
  protected $code = 503;

  /**
   * Defines the description.
   *
   * @var string
   */
  protected $description = 'Service unavailable.';

  /**
   * Defines the problem instance.
   *
   * @var string
   */
  protected $instance = 'help/restful/problem-instances-bad-request';

}

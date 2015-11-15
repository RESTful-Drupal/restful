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
   * {@inheritdoc}
   */
  protected $description = 'Service unavailable.';

  /**
   * {@inheritdoc}
   */
  protected $instance = 'help/restful/problem-instances-bad-request';

}

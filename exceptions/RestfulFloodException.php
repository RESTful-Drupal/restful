<?php

/**
 * @file
 * Contains RestfulFloodException
 */

class RestfulFloodException extends RestfulException {

  /**
   * Defines the HTTP error code.
   *
   * @var int
   */
  protected $code = 429;

  /**
   * {@inheritdoc}
   */
  protected $description = 'Too Many Requests.';

  /**
   * {@inheritdoc}
   */
  protected $instance = 'help/restful/problem-instances-flood';

}

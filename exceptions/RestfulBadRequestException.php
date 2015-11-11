<?php

/**
 * @file
 * Contains RestfulBadRequestException
 */

class RestfulBadRequestException extends RestfulException {

  /**
   * Defines the HTTP error code.
   *
   * @var int
   */
  protected $code = 400;

  /**
   * {@inheritdoc}
   */
  protected $description = 'Bad Request.';

  /**
   * Defines the problem instance.
   *
   * @var string
   */
  protected $instance = 'help/restful/problem-instances-bad-request';

}

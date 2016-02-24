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
   * {@inheritdoc}
   */
  protected $instance = 'help/restful/problem-instances-bad-request';

}

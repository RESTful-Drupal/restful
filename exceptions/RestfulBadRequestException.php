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
   * Defines the description.
   *
   * @var string
   */
  protected $description = 'Bad Request.';
}

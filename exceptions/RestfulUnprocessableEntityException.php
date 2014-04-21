<?php

/**
 * @file
 * Contains RestfulUnprocessableEntityException
 */

class RestfulUnprocessableEntityException extends Exception {

  /**
   * Defines the HTTP error code.
   *
   * @var int
   */
  protected $code = 422;

  /**
   * Defines the description.
   *
   * @var string
   */
  protected $description = 'Unprocessable Entity; Validation errors.';

}

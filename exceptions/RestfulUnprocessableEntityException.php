<?php

/**
 * @file
 * Contains RestfulUnprocessableEntityException
 */

class RestfulUnprocessableEntityException extends RestfulException {

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


  /**
   * Defines the problem instance.
   *
   * @var string
   */
  protected $instance = 'help/restful/problem-instances-unprocessable-entity';

}

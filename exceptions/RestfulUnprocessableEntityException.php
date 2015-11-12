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
   * {@inheritdoc}
   */
  protected $description = 'Unprocessable Entity; Validation errors.';


  /**
   * {@inheritdoc}
   */
  protected $instance = 'help/restful/problem-instances-unprocessable-entity';

}

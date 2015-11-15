<?php

/**
 * @file
 * Contains \Drupal\restful\Exception\UnprocessableEntityException.
 */

namespace Drupal\restful\Exception;

class UnprocessableEntityException extends RestfulException {

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

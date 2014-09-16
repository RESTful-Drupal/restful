<?php

/**
 * @file
 * Contains RestfulNotFoundException
 */

class RestfulNotFoundException extends RestfulException {

  /**
   * Defines the HTTP error code.
   *
   * @var int
   */
  protected $code = 404;

  /**
   * Defines the description.
   *
   * @var string
   */
  protected $description = 'Not Found.';

  /**
   * Defines the problem instance.
   *
   * @var string
   */
  protected $instance = 'help/restful/problem-instances-not-found';

}

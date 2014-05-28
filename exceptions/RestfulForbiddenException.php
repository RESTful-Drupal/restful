<?php

/**
 * @file
 * Contains RestfulForbiddenException
 */

class RestfulForbiddenException extends RestfulException {

  /**
   * Defines the HTTP error code.
   *
   * @var int
   */
  protected $code = 403;

  /**
   * Defines the description.
   *
   * @var string
   */
  protected $description = 'Forbidden.';

  /**
   * Defines the problem instance.
   *
   * @var string
   */
  protected $instance = 'help/restful/problem-instances-forbidden';

}

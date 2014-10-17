<?php

/**
 * @file
 * Contains \RestfulNotImplementedException
 */

class RestfulNotImplementedException extends \RestfulException {

  /**
   * Defines the HTTP error code.
   *
   * @var int
   */
  protected $code = 501;

  /**
   * Defines the description.
   *
   * @var string
   */
  protected $description = 'Not Implemented.';

  /**
   * Defines the problem instance.
   *
   * @var string
   */
  protected $instance = 'help/restful/problem-instances-not-implemented';

}

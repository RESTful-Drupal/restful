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
   * {@inheritdoc}
   */
  protected $description = 'Not Implemented.';

  /**
   * {@inheritdoc}
   */
  protected $instance = 'help/restful/problem-instances-not-implemented';

}

<?php

/**
 * @file
 * Contains RestfulGoneException
 */

class RestfulGoneException extends Exception {

  /**
   * Defines the HTTP error code.
   *
   * @var int
   */
  protected $code = 410;

  /**
   * {@inheritdoc}
   */
  protected $description = 'The resource at this end point is no longer available.';

  /**
   * {@inheritdoc}
   */
  protected $instance = 'help/restful/problem-instances-gone';

}

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
   * {@inheritdoc}
   */
  protected $description = 'Not Found.';

  /**
   * {@inheritdoc}
   */
  protected $instance = 'help/restful/problem-instances-not-found';

}

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
   * {@inheritdoc}
   */
  protected $description = 'Forbidden.';

  /**
   * {@inheritdoc}
   */
  protected $instance = 'help/restful/problem-instances-forbidden';

}

<?php

/**
 * @file
 * Contains RestfulCallbackException
 */

class RestfulCallbackException extends RestfulException {

  /**
   * Defines the HTTP error code.
   *
   * @var int
   */
  protected $code = 430;

  /**
   * Defines the description.
   *
   * @var string
   */
  protected $description = 'Callback function not exists.';
}

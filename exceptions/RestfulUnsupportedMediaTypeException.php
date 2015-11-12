<?php

/**
 * @file
 * Contains RestfulUnsupportedMediaTypeException
 */

class RestfulUnsupportedMediaTypeException extends RestfulException {

  /**
   * Defines the HTTP error code.
   *
   * @var int
   */
  protected $code = 415;

  /**
   * {@inheritdoc}
   */
  protected $description = 'Unsupported Media Type.';

  /**
   * {@inheritdoc}
   */
  protected $instance = 'help/restful/problem-instances-unsupported-media-type';

}

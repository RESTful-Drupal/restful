<?php

/**
 * @file
 * Contains \Drupal\restful\Exception\InaccessibleRecordException.
 */

namespace Drupal\restful\Exception;

/**
 * Class InaccessibleRecordException.
 *
 * @package Drupal\restful\Exception
 */
class InaccessibleRecordException extends RestfulException {

  const ERROR_404_MESSAGE = 'Page not found.';

  /**
   * Instantiates a InaccessibleRecordException object.
   *
   * @param string $message
   *   The exception message.
   */
  public function __construct($message) {
    $show_access_denied = variable_get('restful_show_access_denied', FALSE);
    $this->message = $show_access_denied ? $message : static::ERROR_404_MESSAGE;
    $this->code = $show_access_denied ? 403 : 404;
    $this->instance = $show_access_denied ? 'help/restful/problem-instances-forbidden' : 'help/restful/problem-instances-not-found';
  }

}

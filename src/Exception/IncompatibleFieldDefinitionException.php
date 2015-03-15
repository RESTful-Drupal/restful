<?php

/**
 * @file
 * Contains \Drupal\restful\Exception\IncompatibleFieldDefinitionException.
 */

namespace Drupal\restful\Exception;

class IncompatibleFieldDefinitionException extends RestfulException {

  /**
   * Defines the HTTP error code.
   *
   * @var int
   */
  protected $code = 500;

  /**
   * Defines the description.
   *
   * @var string
   */
  protected $description = 'Incompatible field definition.';

  /**
   * Defines the problem instance.
   *
   * @var string
   */
  protected $instance = 'help/restful/problem-instances-incompatible-field-definition';

}

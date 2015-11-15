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
   * {@inheritdoc}
   */
  protected $description = 'Incompatible field definition.';

  /**
   * {@inheritdoc}
   */
  protected $instance = 'help/restful/problem-instances-incompatible-field-definition';

}

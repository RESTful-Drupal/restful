<?php

/**
 * @file
 * Contains \Drupal\restful\Exception\ServerConfigurationException.
 */

namespace Drupal\restful\Exception;

class ServerConfigurationException extends RestfulException {

  /**
   * Defines the HTTP error code.
   *
   * @var int
   */
  protected $code = 500;

  /**
   * {@inheritdoc}
   */
  protected $description = 'Server configuration error.';

  /**
   * {@inheritdoc}
   */
  protected $instance = 'help/restful/problem-instances-server-configuration';

}

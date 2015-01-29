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
   * Defines the description.
   *
   * @var string
   */
  protected $description = 'Server configuration error.';

  /**
   * Defines the problem instance.
   *
   * @var string
   */
  protected $instance = 'help/restful/problem-instances-server-configuration';

}

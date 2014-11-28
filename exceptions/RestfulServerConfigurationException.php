<?php

/**
 * @file
 * Contains \RestfulServerConfigurationException
 */

class RestfulServerConfigurationException extends \RestfulException {

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

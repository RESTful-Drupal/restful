<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Field\ResourceFieldResourceInterface.
 */

namespace Drupal\restful\Plugin\resource\Field;

interface ResourceFieldResourceInterface extends ResourceFieldInterface {

  /**
   * Gets the ID on the referenced resource.
   *
   * @return string|string[]
   *   The identifier(s) used to access the resource.
   */
  public function getResourceId();

  /**
   * Gets the machine name, without version, of the referenced resource.
   *
   * @return string
   *   The name.
   */
  public function getResourceMachineName();

}

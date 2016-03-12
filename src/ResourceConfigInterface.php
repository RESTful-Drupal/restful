<?php

/**
 * @file
 * Contains \Drupal\restful\ResourceConfigInterface.
 */

namespace Drupal\restful;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Resource Config entities.
 */
interface ResourceConfigInterface extends ConfigEntityInterface {
  // Add get/set methods for your configuration properties here.

  /**
   * Applies default values to the config entity.
   *
   * @param array $values
   *   The aray of YAML values to modify.
   *
   * @return array
   *   The values with the defaults.
   */
  public function addDefaults(array $values);

}

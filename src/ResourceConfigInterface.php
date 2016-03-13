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

  /**
   * Applies default values to the config entity.
   *
   * @param array $values
   *   The aray of YAML values to modify.
   *
   * @return array
   *   The values with the defaults.
   */
  public static function addDefaults(array $values);

}

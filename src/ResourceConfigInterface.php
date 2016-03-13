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

  /**
   * Gets the version.
   *
   * @return string
   *   The version.
   */
  public function getVersion();

  /**
   * Sets the version.
   *
   * @param string $version
   *   The version to set.
   */
  public function setVersion($version);

  /**
   * Gets the path.
   *
   * @return string
   *   The path.
   */
  public function getPath();

  /**
   * Sets the path.
   *
   * @param string $path
   *   The path to set.
   */
  public function setPath($path);

  /**
   * Gets the contentEntityTypeId.
   *
   * @return string
   *   The contentEntityTypeId.
   */
  public function getContentEntityTypeId();

  /**
   * Sets the contentEntityTypeId.
   *
   * @param string $entity_type_id
   *   The contentEntityTypeId to set.
   */
  public function setContentEntityTypeId($entity_type_id);

  /**
   * Gets the contentBundleId.
   *
   * @return string
   *   The contentBundleId.
   */
  public function getContentBundleId();

  /**
   * Sets the contentBundleId.
   *
   * @param string $bundle_id
   *   The contentBundleId to set.
   */
  public function setContentBundleId($bundle_id);

}

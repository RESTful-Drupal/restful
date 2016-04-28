<?php

/**
 * @file
 * Contains \Drupal\restful\Util\RelationalFilterInterface.
 */

namespace Drupal\restful\Util;

/**
 * Interface RelationalFilterInterface.
 *
 * @package Drupal\restful\Util
 */
interface RelationalFilterInterface {

  const TYPE_FIELD = 'field';
  const TYPE_PROPERTY = 'property';

  /**
   * Get the name.
   *
   * @return string
   *   The name.
   */
  public function getName();

  /**
   * Get the type.
   *
   * @return string
   *   The type.
   */
  public function getType();

  /**
   * Gets the entity type.
   *
   * @return string
   *   The type.
   */
  public function getEntityType();

  /**
   * The bundles.
   *
   * @return string[]
   *   Array of bundles.
   */
  public function getBundles();

  /**
   * Database column for field filters.
   *
   * @return string
   *   The DB column.
   */
  public function getColumn();

  /**
   * Database column for the target table relationship.
   *
   * @return string
   *   The DB column.
   */
  public function getTargetColumn();

}

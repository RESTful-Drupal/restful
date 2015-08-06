<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\DataProvider\DataProviderDbQueryInterface.
 */

namespace Drupal\restful\Plugin\resource\DataProvider;

interface DataProviderDbQueryInterface extends DataProviderInterface {

  /**
   * Get the name of the table to query.
   *
   * @return string
   *   The name of the table to query.
   */
  public function getTableName();

  /**
   * Set the name of the table to query.
   *
   * @param string $table_name
   *   The name of the table to query.
   */
  public function setTableName($table_name);

  /**
   * Gets the primary field.
   *
   * @return string
   *   The field name.
   */
  public function getPrimary();

  /**
   * Sets the primary field.
   *
   * @param string $primary
   *   The field name.
   */
  public function setPrimary($primary);

  /**
   * Checks if the current field is the primary field.
   *
   * @param string $field_name
   *   The column name to check.
   *
   * @return bool
   *   TRUE if it is the primary field, FALSE otherwise.
   */
  public function isPrimaryField($field_name);
}

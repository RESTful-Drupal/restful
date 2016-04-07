<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Field\ResourceFieldFilterableInterface.
 */

namespace Drupal\restful\Plugin\resource\Field;

/**
 * Class ResourceFieldFilterableInterface.
 *
 * @package Drupal\restful\Plugin\resource\Field
 */
interface ResourceFieldEntityAlterableInterface {

  /**
   * Alter the list query to add the filtering for this field.
   *
   * @param array $filter
   *   The filter array definition.
   * @param \EntityFieldQuery $query
   *   The entity field query to modify.
   *
   * @return array
   *   The modified $filter array.
   */
  public function alterFilterEntityFieldQuery(array $filter, \EntityFieldQuery $query);

  /**
   * Alter the list query to add the sorting for this field.
   *
   * @param array $sort
   *   The sort array definition.
   * @param \EntityFieldQuery $query
   *   The entity field query to modify.
   *
   * @return array
   *   The modified $sort array.
   */
  public function alterSortEntityFieldQuery(array $sort, \EntityFieldQuery $query);

}

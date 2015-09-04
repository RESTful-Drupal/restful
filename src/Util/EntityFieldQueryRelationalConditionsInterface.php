<?php

/**
 * @file
 * Contains \Drupal\restful\Util\EntityFieldQueryRelationalConditions.
 */

namespace Drupal\restful\Util;

interface EntityFieldQueryRelationalConditionsInterface {

  /**
   * Get the relational filters.
   *
   * @return array[]
   *   The relational filters.
   */
  public function getRelationships();

  /**
   * Add a relational filter.
   *
   * @param array $relational_filter
   *   The filter to add.
   */
  public function addRelationship(array $relational_filter);

}

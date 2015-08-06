<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Field\ResourceFieldDbColumnInterface.
 */

namespace Drupal\restful\Plugin\resource\Field;

interface ResourceFieldDbColumnInterface extends ResourceFieldInterface {

  /**
   * Gets the column for the query.
   *
   * @return string
   *   The name of the column.
   */
  public function getColumnForQuery();

}

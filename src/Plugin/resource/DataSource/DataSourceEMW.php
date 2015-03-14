<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\DataSource\DataSource.
 */

namespace Drupal\restful\Plugin\resource\DataSource;

class DataSourceEMW extends DataSourceBase implements DataSourceInterface {

  /**
   * Returns the \EntityDrupalWrapper.
   *
   * @return \EntityDrupalWrapper
   *   The wrapper describing the entity.
   */
  public function getWrapper() {
    // Note: this is just implemented to override the docblock. Now when we call
    // DataSourceEMW::getWrapper we know we are getting a \EntityDrupalWrapper
    // object back.
    return parent::getWrapper();
  }

}

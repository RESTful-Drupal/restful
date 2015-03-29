<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\DataInterpreter\DataInterpreter.
 */

namespace Drupal\restful\Plugin\resource\DataInterpreter;

class DataInterpreterEMW extends DataInterpreterBase implements DataInterpreterInterface {

  /**
   * Returns the \EntityDrupalWrapper.
   *
   * @return \EntityDrupalWrapper
   *   The wrapper describing the entity.
   */
  public function getWrapper() {
    // Note: this is just implemented to override the docblock. Now when we call
    // DataInterpreterEMW::getWrapper we know we are getting a
    // \EntityDrupalWrapper object back.
    return parent::getWrapper();
  }

}

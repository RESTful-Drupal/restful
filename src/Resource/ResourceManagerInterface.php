<?php

/**
 * @file
 * Contains \Drupal\restful\Resource\ResourceManagerInterface.
 */

namespace Drupal\restful\Resource;

interface ResourceManagerInterface {

  /**
   * Gets the major and minor version for the current request.
   *
   * @return array
   *   The array with the version.
   */
  public function getVersionFromRequest();

}

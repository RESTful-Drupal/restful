<?php

/**
 * @file
 * Contains \RestfulPropertyValueRetrieverInterface.
 */

interface RestfulPropertyValueRetrieverInterface {

  /**
   * Takes the public field configuration and returns the value ready to render.
   *
   * @param array $info
   *   The configuration on how to get the field value.
   * @param \RestfulPropertySourceInterface $source
   *   The object containing the source data to get.
   *
   * @return mixed
   *   The value.
   */
  public function retrieve(array $info, \RestfulPropertySourceInterface $source);

}

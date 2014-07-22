<?php

/**
 * @file
 * Contains \RestfulExampleAngularTagsResource.
 */

class RestfulExampleAngularTagsResource extends \RestfulEntityBase {

  /**
   * Overrides \RestfulEntityBase::getQueryForList().
   *
   * Allow passing the tags types in order to match them.
   *
   * @see taxonomy_autocomplete()
   */
  public function getQueryForList() {
  }
}

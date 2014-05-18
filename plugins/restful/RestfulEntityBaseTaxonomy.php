<?php


/**
 * @file
 * Contains RestfulEntityBaseTaxonomy.
 */

/**
 * A base implementation for "Taxonomy" entity type.
 */
class RestfulEntityBaseTaxonomy extends RestfulEntityBase {

  /**
   * Overrides RestfulEntityBase::getPublicFields().
   */
  public function getPublicFields() {
    $public_fields = parent::getPublicFields();
    $public_fields['label']['property'] = 'name';
    return $public_fields;
  }
}

<?php


/**
 * @file
 * Contains RestfulEntityBaseTaxonomyTerm.
 */

/**
 * A base implementation for "Taxonomy term" entity type.
 */
class RestfulEntityBaseTaxonomyTerm extends RestfulEntityBase {

  /**
   * Overrides \RestfulEntityBase::setPropertyValues().
   *
   * Set the "vid" property on new terms.
   */
  protected function setPropertyValues(EntityMetadataWrapper $wrapper, $null_missing_fields = FALSE) {
    $term = $wrapper->value();
    if (!empty($term->tid)) {
      return;
    }

    $vocabulary = taxonomy_vocabulary_machine_name_load($term->vocabulary_machine_name);
    $term->vid = $vocabulary->vid;

    parent::setPropertyValues($wrapper, $null_missing_fields);
  }

  /**
   * Overrides \RestfulEntityBase::checkPropertyAccess().
   *
   * Allow user to create a label for the unsaved term, even if the user doesn't
   * have access to update existing terms, as required by the entity metadata
   * wrapper's access check.
   */
  protected function checkPropertyAccess($op, $public_field_name, EntityMetadataWrapper $property, EntityMetadataWrapper $wrapper) {
    $info = $property->info();
    $term = $wrapper->value();
    if (!empty($info['name']) && $info['name'] == 'name' && empty($term->tid) && $op == 'edit') {
      return TRUE;
    }
    return parent::checkPropertyAccess($op, $public_field_name, $property, $wrapper);
  }
}

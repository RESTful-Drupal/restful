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
    if (empty($term->tid) && (!taxonomy_vocabulary_machine_name_load($this->getBundle()))) {
      // This is a new term object but we don't have a vocabulary with the name
      // from the plugin definition.
      return;
    }

    $vocabulary_name = empty($term->vocabulary_machine_name) ? $this->getBundle() : $term->vocabulary_machine_name;
    $vocabulary = taxonomy_vocabulary_machine_name_load($vocabulary_name);
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

  /**
   * Overrides \RestfulEntityBaseTaxonomyTerm::checkEntityAccess().
   *
   * Allow access to create "Tags" resource for privileged users, as
   * we can't use entity_access() since entity_metadata_taxonomy_access()
   * denies it for a non-admin user.
   */
  protected function checkEntityAccess($op, $entity_type, $entity) {
    $account = $this->getAccount();
    return user_access($op == 'view' ? 'access content' : 'administer taxonomy', $account);
  }
}

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

    $vocabulary = taxonomy_vocabulary_machine_name_load($this->getBundle());
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
   * Taxonomy access for different operations has defined in the menu in various
   * ways. This method will implement the same access logic of the menu items.
   */
  protected function checkEntityAccess($op, $entity_type, $entity) {
    if ($this->getMethod() == \RestfulBase::GET) {
      $permission = 'access content';
    }
    else {
      if ($this->getMethod() == \RestfulBase::POST) {
        $permission = 'administer taxonomy';
      }
      else {
        $vocabulary = taxonomy_vocabulary_machine_name_load($this->getBundle());
        $operation = $this->getMethod() == \RestfulBase::DELETE ? 'delete' : 'edit';
        $permission = $operation . ' terms in ' . $vocabulary->vid;
      }
    }

    return user_access($permission, $this->getAccount());
  }
}

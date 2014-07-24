<?php

/**
 * @file
 * Contains \RestfulEntityTaxonomyTermTags.
 */

class RestfulEntityTaxonomyTermTags extends \RestfulEntityBaseTaxonomyTerm {

  /**
   * Overrides \RestfulEntityBaseTaxonomyTerm::createEntity().
   *
   * Allow access to create "Tags" resource for privileged users, as
   * we can't use entity_access() since entity_metadata_taxonomy_access()
   * denies it for a non-admin user.
   */
  public function createEntity() {
    $account = $this->getAccount();
    if (!user_access('create article content', $account)) {
      // User does not have access to create entity.
      $params = array('@resource' => $this->plugin['label']);
      throw new RestfulForbiddenException(format_string('You do not have access to create a new @resource resource.', $params));
    }

    $entity_info = entity_get_info($this->entityType);
    $bundle_key = $entity_info['entity keys']['bundle'];
    $values = array($bundle_key => $this->bundle);

    $entity = entity_create($this->entityType, $values);

    $wrapper = entity_metadata_wrapper($this->entityType, $entity);

    $this->setPropertyValues($wrapper);
    return $this->viewEntity($wrapper->getIdentifier());
  }

  /**
   * Overrides \RestfulEntityBaseTaxonomyTerm::createEntity().
   *
   * Allow user to create a label for the term.
   */
  protected function checkPropertyAccess(EntityMetadataWrapper $property, $op = 'edit', EntityMetadataWrapper $wrapper) {
    $info = $property->info();
    $term = $wrapper->value();
    if (!empty($info['name']) && $info['name'] == 'name' && empty($term->tid) && $op == 'edit') {
      return TRUE;
    }
    return parent::checkPropertyAccess($property, $op, $wrapper);
  }
}

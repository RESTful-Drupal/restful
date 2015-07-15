<?php

/**
 * @file
 * Contains \RestfulEntityTaxonomyTermTestTags.
 */

class RestfulEntityTaxonomyTermTestTags extends \RestfulEntityBaseTaxonomyTerm {

  /**
   * Overrides \RestfulEntityBaseTaxonomyTerm::checkEntityAccess().
   *
   * Allow access to create "Tags" resource for privileged users, as
   * we can't use entity_access() since entity_metadata_taxonomy_access()
   * denies it for a non-admin user.
   */
  protected function checkEntityAccess($op, $entity_type, $entity) {
    $account = $this->getAccount();
    return parent::checkEntityAccess($op, $entity_type, $entity) || user_access('create article content', $account);
  }
}

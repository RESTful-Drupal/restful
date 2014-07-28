<?php

/**
 * @file
 * Contains \RestfulEntityTaxonomyTermTags.
 */

class RestfulEntityTaxonomyTermTags extends \RestfulEntityBaseTaxonomyTerm {

  /**
   * Overrides \RestfulEntityBaseTaxonomyTerm::checkEntityAccess().
   *
   * Allow access to create "Tags" resource for privileged users, as
   * we can't use entity_access() since entity_metadata_taxonomy_access()
   * denies it for a non-admin user.
   */
  protected function checkEntityAccess($op, $entity_type, $entity) {
    $account = $this->getAccount();
    return user_access('create article content', $account);
  }
}

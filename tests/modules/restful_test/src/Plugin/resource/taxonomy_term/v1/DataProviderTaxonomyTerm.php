<?php

/**
 * @file
 * Contains \Drupal\restful_test\Plugin\resource\taxonomy_term\v1\DataProviderTaxonomyTerm.
 */

namespace Drupal\restful_test\Plugin\resource\taxonomy_term\v1;

use Drupal\restful\Plugin\resource\DataProvider\DataProviderTaxonomyTerm as DataProviderTaxonomyTermOriginal;

class DataProviderTaxonomyTerm extends DataProviderTaxonomyTermOriginal {

  /**
   * {@inheritdoc}
   */
  protected function checkEntityAccess($op, $entity_type, $entity) {
    $account = $this->getAccount();
    return user_access('create article content', $account);
  }

}

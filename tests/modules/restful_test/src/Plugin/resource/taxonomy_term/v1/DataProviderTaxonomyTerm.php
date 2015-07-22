<?php

/**
 * @file
 * Contains \Drupal\restful_test\Plugin\resource\taxonomy_term\v1\DataProviderTaxonomyTerm.
 */

namespace Drupal\restful_test\Plugin\resource\taxonomy_term\v1;

use Drupal\restful\Plugin\resource\DataInterpreter\DataInterpreterInterface;
use Drupal\restful\Plugin\resource\DataProvider\DataProviderTaxonomyTerm as DataProviderTaxonomyTermOriginal;
use Drupal\restful\Plugin\resource\Field\ResourceFieldInterface;

class DataProviderTaxonomyTerm extends DataProviderTaxonomyTermOriginal {

  /**
   * {@inheritdoc}
   */
  protected function checkEntityAccess($op, $entity_type, $entity) {
    $account = $this->getAccount();
    return user_access('create article content', $account);
  }

  /**
   * {@inheritdoc}
   */
  protected static function checkPropertyAccess(ResourceFieldInterface $resource_field, $op, DataInterpreterInterface $interpreter) {
    $term = $interpreter->getWrapper()->value();
    if ($resource_field->getProperty() == 'name' && empty($term->tid) && $op == 'edit') {
      return TRUE;
    }
    return parent::checkPropertyAccess($resource_field, $op, $interpreter);
  }

}

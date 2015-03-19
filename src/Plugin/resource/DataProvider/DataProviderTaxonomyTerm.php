<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\DataProvider\DataProviderEntity.
 */

namespace Drupal\restful\Plugin\resource\DataProvider;

class DataProviderTaxonomyTerm extends DataProviderEntity implements DataProviderEntityInterface {


  /**
   * Overrides DataProviderEntity::setPropertyValues().
   */
  protected function setPropertyValues(\EntityDrupalWrapper $wrapper, array $object, $replace = FALSE) {
    $term = $wrapper->value();
    if (!empty($term->vid)) {
      return;
    }

    $vocabulary = taxonomy_vocabulary_machine_name_load($term->vocabulary_machine_name);
    $term->vid = $vocabulary->vid;

    parent::setPropertyValues($wrapper, $object, $replace);
  }

}

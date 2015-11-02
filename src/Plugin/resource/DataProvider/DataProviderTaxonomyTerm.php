<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\DataProvider\DataProviderEntity.
 */

namespace Drupal\restful\Plugin\resource\DataProvider;

class DataProviderTaxonomyTerm extends DataProviderEntity implements DataProviderEntityInterface {


  /**
   * Overrides DataProviderEntity::setPropertyValues().
   *
   * This class is created to override this method. This method is overridden to
   * add the vocabulary ID based on the vocabulary machine name when creating a
   * taxonomy term.
   */
  protected function setPropertyValues(\EntityDrupalWrapper $wrapper, $object, $replace = FALSE) {
    $term = $wrapper->value();
    if (empty($term->vid)) {
      $vocabulary = taxonomy_vocabulary_machine_name_load($term->vocabulary_machine_name);
      $term->vid = $vocabulary->vid;
    }

    parent::setPropertyValues($wrapper, $object, $replace);
  }

}

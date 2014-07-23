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
   * Return the bundles that should be used for the autocomplete search.
   *
   * @return array
   *   Array with the vocabulary IDs.
   */
  protected function getListByAutocompleteBundles() {
    $vocabulary = taxonomy_vocabulary_machine_name_load($this->getBundle());
    return array($vocabulary->vid);
  }

  /**
   * Returns the result of a query for the auto complete.
   *
   * @param string $string
   *   The string to query.
   * @param int $range
   *   The range of the query.
   *
   * @return array
   *   Array keyed by the entity ID and the entity label as value.
   */
  protected function getListByAutocompleteQueryResult($string, $range) {
    $bundles = $this->getListByAutocompleteBundles();

    $query = db_select('taxonomy_term_data', 't');
    $query->addTag('translatable');
    $query->addTag('term_access');

    // Select rows that match by term name.
    return $query
      ->fields('t', array('tid', 'name'))
      ->condition('t.vid', $bundles, 'IN')
      ->condition('t.name', '%' . db_like($string) . '%', 'LIKE')
      ->range(0, $range)
      ->execute()
      ->fetchAllKeyed();
  }
}

<?php

/**
 * @file
 * Contains \RestfulExampleAngularTagsResource.
 */

class RestfulExampleAngularTagsResource extends \RestfulEntityBase {

  /**
   * Overrides \RestfulEntityBase::getList().
   *
   * Allow passing the tags types in order to match them.
   */
  public function getList() {
    $request = $this->getRequest();
    return !isset($request['string']) ? parent::getList() : $this->taxonomyAutocomplete();
  }

  /**
   * Return the values of the types tags, with the ID.
   *
   * @return array
   *   Array with the found terms keys by the entity ID.
   *   ID. Otherwise, if the field allows auto-creating tags, the ID will be the
   *   term name, to indicate for client it is an unsaved term.
   *
   * @see taxonomy_autocomplete()
   */
  protected function getListByAutocomplete() {
    $request = $this->getRequest();
    if (empty($request['string'])) {
      // Empty string.
      return array();
    }

    $string = drupal_strtolower($request['string']);
    $options = $this->getPluginInfo('options');
    $range = $options['autocomplete']['range'];

    $result = $this->getListByAutocompleteQueryResult($string, $range);

    foreach ($result as $entity_id => $label) {
      $string_matches[$entity_id] = check_plain($label);
    }

    return $string_matches;
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

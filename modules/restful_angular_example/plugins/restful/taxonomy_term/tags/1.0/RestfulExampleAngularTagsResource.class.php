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
    if (!isset($request['q'])) {
      return parent::getList();
    }
  }

  /**
   * Return the values of the types tags, with the ID.
   *
   * @return array
   *   Array with the found terms which will have the ID populated with the term
   *   ID. Otherwise, if the field allows auto-creating tags, the ID will be the
   *   term name, to indicate for client it is an unsaved term.
   *
   * @see taxonomy_autocomplete()
   */
  protected function taxonomyAutocomplete() {
    $request = $this->getRequest();
    if (empty($request['q'])) {
      // Empty string.
      return array();
    }

    $term = drupal_strtolower($request['q']);


    $term_matches = array();

    // Part of the criteria for the query come from the field's own settings.
    $vocabulary = taxonomy_vocabulary_machine_name_load($this->getBundle());
    $vids = array($vocabulary->vid);

    $query = db_select('taxonomy_term_data', 't');
    $query->addTag('translatable');
    $query->addTag('term_access');

    // Select rows that match by term name.
    $tags_return = $query
      ->fields('t', array('tid', 'name'))
      ->condition('t.vid', $vids)
      ->condition('t.name', '%' . db_like($term) . '%', 'LIKE')
      ->range(0, 10)
      ->execute()
      ->fetchAllKeyed();

    foreach ($tags_return as $tid => $name) {
      $term_matches[$tid] = check_plain($name);
    }

    if (!$tags_return) {
      // No result found.
      $term_matches[$term] = check_plain($term);
    }

    return $term_matches;
  }
}

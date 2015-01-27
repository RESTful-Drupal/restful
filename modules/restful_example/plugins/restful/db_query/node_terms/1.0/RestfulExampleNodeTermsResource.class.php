<?php

/**
 * @file
 * Contains RestfulExampleNodeTermsResource.
 */

class RestfulExampleNodeTermsResource extends \RestfulDataProviderDbQuery implements \RestfulDataProviderDbQueryInterface {

  /**
   * {@inheritdoc}
   */
  public function publicFieldsInfo() {
    $public_fields['id'] = array(
      'property' => 'nid',
    );

    $public_fields['label'] = array(
      'property' => 'title',
    );

    // The terms are taken from a join query, as they exist on another table.
    $public_fields['terms'] = array(
      'property' => 'terms',
    );

    return $public_fields;
  }

  /**
   * Overrides \RestfulDataProviderDbQuery::getQuery().
   *
   * Join with the terms table.
   */
  protected function getQuery() {
    $query = parent::getQuery();

    $field = field_info_field('field_tags');
    $table_name = _field_sql_storage_tablename($field);
    $query->join($table_name, 'terms', 'id = terms.entity_id');
    
    $query->condition('terms.entity_type', 'node');

    return $query;
  }

}

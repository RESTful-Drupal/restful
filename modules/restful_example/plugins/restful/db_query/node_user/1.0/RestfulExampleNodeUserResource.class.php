<?php

/**
 * @file
 * Contains RestfulExampleNodeUserResource.
 */

class RestfulExampleNodeUserResource extends \RestfulDataProviderDbQuery implements \RestfulDataProviderDbQueryInterface {

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
    $public_fields['author'] = array(
      'property' => 'name',

      // Set the actual column name, so WHERE and ORDER BY may work, as MySql
      // doesn't allow using a column alias for those operations.
      'column_for_query' => 'user.name',
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

    // Add a node access tag.
    $query->addTag('node_access');

    $query->innerJoin('users', 'user', 'node.uid = user.uid');

    // Explicitly set the alias of the column, so it will match the public field
    // name.
    $query->addField('user', 'name', 'name');

    return $query;
  }

}

<?php

/**
 * @file
 * Contains \Drupal\restful\Util\EntityFieldQuery.
 */

namespace Drupal\restful\Util;

use Drupal\restful\Exception\ServerConfigurationException;

class EntityFieldQuery extends \EntityFieldQuery implements EntityFieldQueryRelationalConditionsInterface {

  /**
   * The relational filters.
   *
   * @var array[]
   */
  protected $relationships = array();

  /**
   * {@inheritdoc}
   */
  public function getRelationships() {
    return $this->relationships;
  }

  /**
   * {@inheritdoc}
   */
  public function addRelationship(array $relational_filter) {
    $this->relationships[] = $relational_filter;
  }

  /**
   * {@inheritdoc}
   */
  public function finishQuery($select_query, $id_key = 'entity_id') {
    $entity_type = $this->entityConditions['entity_type']['value'];
    foreach ($this->getRelationships() as $delta => $relationship) {
      // A relational filter consists of a chain of relationships and a value
      // for a condition at the end.
      // Relationships start with the entity base table.
      $entity_info = entity_get_info($entity_type);
      $entity_table = $entity_table_alias = $entity_info['base table'];

      // Add the table if the base entity table was not added because:
      // 1. There was a fieldCondition or fieldOrderBy, AND
      // 2. There was no property condition ot order.
      if ($delta == 0) {
        $is_entity_table_present = FALSE;
        $field_base_table_alias = NULL;
        foreach ($select_query->getTables() as $table_info) {
          // Search for the base table and check if the entity table is present
          // for the resource's entity type.
          if (!$field_base_table_alias && empty($table_info['join type'])) {
            $field_base_table_alias = $table_info['alias'];
          }
          if ($table_info['table'] == $entity_table) {
            $is_entity_table_present = TRUE;
            break;
          }
        }
        if (!$is_entity_table_present && $field_base_table_alias) {
          // We have the base table and we need to join it to the entity table.
          _field_sql_storage_query_join_entity($select_query, $entity_type, $field_base_table_alias);
        }
      }
      // Pop the last item, since it is the one that has to match the filter and
      // will have the WHERE associated.
      foreach ($relationship['relational_filters'] as $relational_filter) {
        /* @var RelationalFilterInterface $relational_filter */
        if ($relational_filter->getType() == RelationalFilterInterface::TYPE_FIELD) {
          $field_table_name = _field_sql_storage_tablename(field_info_field($relational_filter->getName()));
          $field_table_alias = $this::aliasJoinTable($field_table_name, $select_query);
          $select_query->addJoin('INNER', $field_table_name, $field_table_alias, sprintf('%s.%s = %s.%s',
            $entity_table_alias,
            $entity_info['entity keys']['id'],
            $field_table_alias,
            $id_key
          ));
          // Get the entity type being referenced.
          $entity_info = entity_get_info($relational_filter->getEntityType());
          $entity_table_alias = $this->aliasJoinTable($entity_info['base table'], $select_query);
          $select_query->addJoin('INNER', $entity_info['base table'], $entity_table_alias, sprintf('%s.%s = %s.%s',
            $field_table_name,
            _field_sql_storage_columnname($relational_filter->getName(), $relational_filter->getColumn()),
            $entity_table_alias,
            $entity_info['entity keys']['id']
          ));
        }
        elseif ($relational_filter->getType() == RelationalFilterInterface::TYPE_PROPERTY) {
          // We only know about the uid in this scenario.
          if ($relational_filter->getName() == 'uid') {
            $user_table_alias = $this::aliasJoinTable('users', $select_query);
            $select_query->addJoin('INNER', 'users', $user_table_alias, sprintf('%s.uid = %s.uid',
              $entity_table,
              $user_table_alias
            ));
          }
          throw new ServerConfigurationException(sprintf('Unsupported nested filter on property %s', $relational_filter->getName()));
        }
      }
      /* @var RelationalFilterInterface $condition */
      if ($condition->getType() == RelationalFilterInterface::TYPE_FIELD) {
        // Make the join to the filed table for the condition.
        $field_table_name = _field_sql_storage_tablename(field_info_field($condition->getName()));
        $field_column = _field_sql_storage_columnname($condition->getName(), $condition->getColumn());
        $field_table_alias = $this::aliasJoinTable($field_table_name, $select_query);
        $select_query->addJoin('INNER', $field_table_name, $field_table_alias, sprintf('%s.%s = %s.%s',
          $entity_table_alias,
          $entity_info['entity keys']['id'],
          $field_table_alias,
          $id_key
        ));
        if (in_array($relationship['operator'], array('IN', 'BETWEEN'))) {
          $select_query->condition($field_table_name . '.' . $field_column, $relationship['value'], $relationship['operator'][0]);
        }
        else {
          for ($index = 0; $index < count($relationship['value']); $index++) {
            $select_query->condition($field_table_name . '.' . $field_column, $relationship['value'][$index], $relationship['operator'][$index]);
          }
        }
      }
      elseif ($condition->getType() == RelationalFilterInterface::TYPE_PROPERTY) {
        if (in_array($relationship['operator'], array('IN', 'BETWEEN'))) {
          $select_query->condition($entity_table_alias . '.' . $condition->getName(), $relationship['value'], $relationship['operator'][0]);
        }
        else {
          for ($index = 0; $index < count($relationship['value']); $index++) {
            $select_query->condition($entity_table_alias . '.' . $condition->getName(), $relationship['value'][$index], $relationship['operator'][$index]);
          }
        }
      }
    }
    return parent::finishQuery($select_query, $id_key);
  }

  /**
   * Helper function tha checks if the select query already has a join.
   *
   * @param string $table_name
   *   The name of the table.
   * @param \SelectQuery $query
   *   The query.
   *
   * @return string
   *   The table alias.
   */
  protected static function aliasJoinTable($table_name, \SelectQuery $query) {
    foreach ($query->getTables() as $table_info) {
      if ($table_info['table'] == $table_name) {
        $matches = array();
        preg_match('/.*_(\d+)$/', $table_name, $matches);
        $num = empty($matches[1]) ? -1 : $matches[1];
        return static::aliasJoinTable($table_name . '_' . ($num + 1), $query);
      }
    }
    return $table_name;
  }

}

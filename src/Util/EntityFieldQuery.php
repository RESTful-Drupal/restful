<?php

/**
 * @file
 * Contains \Drupal\restful\Util\EntityFieldQuery.
 */

namespace Drupal\restful\Util;

use Drupal\restful\Exception\ServerConfigurationException;
use SelectQuery;

class EntityFieldQuery extends \EntityFieldQuery implements EntityFieldQueryRelationalConditionsInterface {

  /**
   * The relational filters.
   *
   * @var array[]
   */
  protected $relationships = array();

  /**
   * List of operators that require a LEFT JOIN instead of an INNER JOIN.
   *
   * @var array
   */
  protected static $leftJoinOperators = array(
    'NOT IN',
    'IS NULL',
    'IS NOT NULL',
    '<>',
    'NOT BETWEEN',
    'NOT LIKE',
  );

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
  public function queryCallback() {
    // Scan all the field conditions to see if there is any operator that needs
    // a left join. If there is none, use the default behavior.
    $left_field_conditions = array_filter($this->fieldConditions, function ($field_condition) {
      return !empty($field_condition['operator']) && in_array($field_condition['operator'], static::$leftJoinOperators);
    });
    return empty($left_field_conditions) ? parent::queryCallback() : array($this, 'buildQuery');
  }

  /**
   * Builds the SelectQuery and executes finishQuery().
   */
  protected function buildQuery() {
    // Make the query be based on the entity table so we can get all the
    // entities.
    $select_query = $this->prePropertyQuery();
    list($select_query, $id_key) = $this->fieldStorageQuery($select_query);
    return $this->finishQuery($select_query, $id_key);
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
      // 2. There was no property condition or order.
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
      $condition = array_pop($relationship['relational_filters']);
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
          $entity_table_alias = $this::aliasJoinTable($entity_info['base table'], $select_query);
          $select_query->addJoin('INNER', $entity_info['base table'], $entity_table_alias, sprintf('%s.%s = %s.%s',
            $field_table_name,
            _field_sql_storage_columnname($relational_filter->getName(), $relational_filter->getColumn()),
            $entity_table_alias,
            $relational_filter->getTargetColumn()
          ));
        }
        elseif ($relational_filter->getType() == RelationalFilterInterface::TYPE_PROPERTY) {
          // In this scenario we want to join with the new table entity. This
          // will only work if the property contains the referenced entity ID
          // (which is not unreasonable).
          $host_entity_table = $entity_table_alias;
          $entity_info = entity_get_info($relational_filter->getEntityType());
          $entity_table_alias = $this::aliasJoinTable($entity_info['base table'], $select_query);
          $select_query->addJoin('INNER', $entity_info['base table'], $entity_table_alias, sprintf('%s.%s = %s.%s',
            $host_entity_table,
            $relational_filter->getName(),
            $entity_table_alias,
            $relational_filter->getTargetColumn()
          ));
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
   * @param SelectQuery $query
   *   The query.
   *
   * @return string
   *   The table alias.
   */
  protected static function aliasJoinTable($table_name, SelectQuery $query) {
    foreach ($query->getTables() as $table_info) {
      if ($table_info['alias'] == $table_name) {
        $matches = array();
        preg_match('/.*_(\d+)$/', $table_name, $matches);
        $num = empty($matches[1]) ? -1 : $matches[1];
        return static::aliasJoinTable($table_name . '_' . ($num + 1), $query);
      }
    }
    return $table_name;
  }

  /**
   * Copy of propertyQuery() without the finishQuery execution.
   *
   * @see \EntityFieldQuery::propertyQuery()
   */
  protected function prePropertyQuery() {
    if (empty($this->entityConditions['entity_type'])) {
      throw new \EntityFieldQueryException(t('For this query an entity type must be specified.'));
    }
    $entity_type = $this->entityConditions['entity_type']['value'];
    $entity_info = entity_get_info($entity_type);
    if (empty($entity_info['base table'])) {
      throw new \EntityFieldQueryException(t('Entity %entity has no base table.', array('%entity' => $entity_type)));
    }
    $base_table = $entity_info['base table'];
    $base_table_schema = drupal_get_schema($base_table);
    $select_query = db_select($base_table);
    $select_query->addExpression(':entity_type', 'entity_type', array(':entity_type' => $entity_type));
    // Process the property conditions.
    foreach ($this->propertyConditions as $property_condition) {
      $this->addCondition($select_query, $base_table . '.' . $property_condition['column'], $property_condition);
    }
    // Process the four possible entity condition.
    // The id field is always present in entity keys.
    $sql_field = $entity_info['entity keys']['id'];
    $this->addMetaData('base_table', $base_table);
    $this->addMetaData('entity_id_key', $sql_field);
    $id_map['entity_id'] = $sql_field;
    $select_query->addField($base_table, $sql_field, 'entity_id');
    if (isset($this->entityConditions['entity_id'])) {
      $this->addCondition($select_query, $base_table . '.' . $sql_field, $this->entityConditions['entity_id']);
    }

    // If there is a revision key defined, use it.
    if (!empty($entity_info['entity keys']['revision'])) {
      $sql_field = $entity_info['entity keys']['revision'];
      $select_query->addField($base_table, $sql_field, 'revision_id');
      if (isset($this->entityConditions['revision_id'])) {
        $this->addCondition($select_query, $base_table . '.' . $sql_field, $this->entityConditions['revision_id']);
      }
    }
    else {
      $sql_field = 'revision_id';
      $select_query->addExpression('NULL', 'revision_id');
    }
    $id_map['revision_id'] = $sql_field;

    // Handle bundles.
    if (!empty($entity_info['entity keys']['bundle'])) {
      $sql_field = $entity_info['entity keys']['bundle'];
      $having = FALSE;

      if (!empty($base_table_schema['fields'][$sql_field])) {
        $select_query->addField($base_table, $sql_field, 'bundle');
      }
    }
    else {
      $sql_field = 'bundle';
      $select_query->addExpression(':bundle', 'bundle', array(':bundle' => $entity_type));
      $having = TRUE;
    }
    $id_map['bundle'] = $sql_field;
    if (isset($this->entityConditions['bundle'])) {
      if (!empty($entity_info['entity keys']['bundle'])) {
        $this->addCondition($select_query, $base_table . '.' . $sql_field, $this->entityConditions['bundle'], $having);
      }
      else {
        // This entity has no bundle, so invalidate the query.
        $select_query->where('1 = 0');
      }
    }

    // Order the query.
    foreach ($this->order as $order) {
      if ($order['type'] == 'entity') {
        $key = $order['specifier'];
        if (!isset($id_map[$key])) {
          throw new \EntityFieldQueryException(t('Do not know how to order on @key for @entity_type', array('@key' => $key, '@entity_type' => $entity_type)));
        }
        $select_query->orderBy($id_map[$key], $order['direction']);
      }
      elseif ($order['type'] == 'property') {
        $select_query->orderBy($base_table . '.' . $order['specifier'], $order['direction']);
      }
    }

    return $select_query;
  }

  /**
   * Copies field_sql_storage_field_storage_query() using left joins some times.
   *
   * @see field_sql_storage_field_storage_query()
   */
  protected function fieldStorageQuery(SelectQuery $select_query) {
    if ($this->age == FIELD_LOAD_CURRENT) {
      $tablename_function = '_field_sql_storage_tablename';
      $id_key = 'entity_id';
    }
    else {
      $tablename_function = '_field_sql_storage_revision_tablename';
      $id_key = 'revision_id';
    }
    $table_aliases = array();
    $query_tables = NULL;
    $base_table = $this->metaData['base_table'];

    // Add tables for the fields used.
    $field_base_table = NULL;
    foreach ($this->fields as $key => $field) {
      $tablename = $tablename_function($field);
      $table_alias = _field_sql_storage_tablealias($tablename, $key, $this);
      $table_aliases[$key] = $table_alias;
      $select_query->addMetaData('base_table', $base_table);
      $entity_id_key = $this->metaData['entity_id_key'];
      if ($field_base_table) {
        if (!isset($query_tables[$table_alias])) {
          $this->addFieldJoin($select_query, $field['field_name'], $tablename, $table_alias, "$table_alias.entity_type = $field_base_table.entity_type AND $table_alias.$id_key = $field_base_table.$id_key");
        }
      }
      else {
        // By executing prePropertyQuery() we made sure that the base table is
        // the entity table.
        $this->addFieldJoin($select_query, $field['field_name'], $tablename, $table_alias, "$base_table.$entity_id_key = $table_alias.$id_key");
        // Store a reference to the list of joined tables.
        $query_tables =& $select_query->getTables();
        // Allow queries internal to the Field API to opt out of the access
        // check, for situations where the query's results should not depend on
        // the access grants for the current user.
        if (!isset($this->tags['DANGEROUS_ACCESS_CHECK_OPT_OUT'])) {
          $select_query->addTag('entity_field_access');
        }
        if (!$this->containsLeftJoinOperator($this->fields[$key]['field_name'])) {
          $field_base_table = $table_alias;
        }
      }
      if ($field['cardinality'] != 1 || $field['translatable']) {
        $select_query->distinct();
      }
    }

    // Add field conditions. We need a fresh grouping cache.
    drupal_static_reset('_field_sql_storage_query_field_conditions');
    _field_sql_storage_query_field_conditions($this, $select_query, $this->fieldConditions, $table_aliases, '_field_sql_storage_columnname');

    // Add field meta conditions.
    _field_sql_storage_query_field_conditions($this, $select_query, $this->fieldMetaConditions, $table_aliases, '_field_sql_storage_query_columnname');

    // If there was no field condition that created an INNER JOIN, that means
    // that additional JOINs need to carry the OR condition. For the base table
    // we'll use the table for the first field.
    $needs_or = FALSE;
    if (!isset($field_base_table)) {
      $needs_or = TRUE;
      // Get the table name for the first field.
      $field_table_name = key($this->fields[0]['storage']['details']['sql'][$this->age]);
      $field_base_table = _field_sql_storage_tablealias($field_table_name, 0, $this);
    }

    if (isset($this->deleted)) {
      $delete_condition = array(
        'value' => (int) $this->deleted,
        'operator' => '=',
        'or' => $needs_or,
      );
      $this->addCondition($select_query, "$field_base_table.deleted", $delete_condition);
    }

    foreach ($this->entityConditions as $key => $condition) {
      $condition['or'] = $needs_or;
      $this->addCondition($select_query, "$field_base_table.$key", $condition);
    }

    // Order the query.
    foreach ($this->order as $order) {
      if ($order['type'] == 'entity') {
        $key = $order['specifier'];
        $select_query->orderBy("$field_base_table.$key", $order['direction']);
      }
      elseif ($order['type'] == 'field') {
        $specifier = $order['specifier'];
        $field = $specifier['field'];
        $table_alias = $table_aliases[$specifier['index']];
        $sql_field = "$table_alias." . _field_sql_storage_columnname($field['field_name'], $specifier['column']);
        $select_query->orderBy($sql_field, $order['direction']);
      }
      elseif ($order['type'] == 'property') {
        $select_query->orderBy("$base_table." . $order['specifier'], $order['direction']);
      }
    }

    return array($select_query, $id_key);
  }

  /**
   * Adds a join to the field table with the appropriate join type.
   *
   * @param SelectQuery $select_query
   *   The select query to modify.
   * @param string $field_name
   *   The name of the field to join.
   * @param string $table
   *   The table against which to join.
   * @param string $alias
   *   The alias for the table. In most cases this should be the first letter
   *   of the table, or the first letter of each "word" in the table.
   * @param string $condition
   *   The condition on which to join this table. If the join requires values,
   *   this clause should use a named placeholder and the value or values to
   *   insert should be passed in the 4th parameter. For the first table joined
   *   on a query, this value is ignored as the first table is taken as the base
   *   table. The token %alias can be used in this string to be replaced with
   *   the actual alias. This is useful when $alias is modified by the database
   *   system, for example, when joining the same table more than once.
   * @param array $arguments
   *   An array of arguments to replace into the $condition of this join.
   *
   * @return string
   *   The unique alias that was assigned for this table.
   */
  protected function addFieldJoin(SelectQuery $select_query, $field_name, $table, $alias = NULL, $condition = NULL, $arguments = array()) {
    // Find if we need a left or inner join by inspecting the field conditions.
    $type = 'INNER';
    foreach ($this->fieldConditions as $field_condition) {
      if ($field_condition['field']['field_name'] == $field_name) {
        $type = in_array($field_condition['operator'], static::$leftJoinOperators) ? 'LEFT' : 'INNER';
        break;
      }
    }
    return $select_query->addJoin($type, $table, $alias, $condition, $arguments);
  }

  /**
   * Adds a condition to an already built SelectQuery (internal function).
   *
   * This is a helper for hook_entity_query() and hook_field_storage_query().
   *
   * @param SelectQuery $select_query
   *   A SelectQuery object.
   * @param string $sql_field
   *   The name of the field.
   * @param array $condition
   *   A condition as described in EntityFieldQuery::fieldCondition() and
   *   EntityFieldQuery::entityCondition().
   * @param bool $having
   *   HAVING or WHERE. This is necessary because SQL can't handle WHERE
   *   conditions on aliased columns.
   */
  public function addCondition(SelectQuery $select_query, $sql_field, $condition, $having = FALSE) {
    $needs_or = !empty($condition['or']) || in_array($condition['operator'], static::$leftJoinOperators);
    if (
      in_array($condition['operator'], array('CONTAINS', 'STARTS_WITH')) ||
      !$needs_or
    ) {
      parent::addCondition($select_query, $sql_field, $condition, $having);
      return;
    }
    $method = $having ? 'havingCondition' : 'condition';
    $db_or = db_or()->condition($sql_field, $condition['value'], $condition['operator']);
    if (strtoupper($condition['operator']) != 'IS NULL' && strtoupper($condition['operator']) != 'IS NOT NULL') {
      $db_or->condition($sql_field, NULL, 'IS NULL');
    }
    $select_query->$method($db_or);
  }

  /**
   * Checks if any of the conditions contains a LEFT JOIN operation.
   *
   * @param string $field_name
   *   If provided only this field will be checked.
   *
   * @return bool
   *   TRUE if any of the conditions contain a left join operator.
   */
  protected function containsLeftJoinOperator($field_name = NULL) {
    foreach ($this->fieldConditions as $field_condition) {
      if ($field_name && $field_condition['field']['field_name'] != $field_name) {
        continue;
      }
      if (in_array($field_condition['operator'], static::$leftJoinOperators)) {
        return TRUE;
      }
    }
    return FALSE;
  }

}

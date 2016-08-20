<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\DataProvider\DataProviderDbQuery.
 */

namespace Drupal\restful\Plugin\resource\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Drupal\restful\Exception\BadRequestException;
use Drupal\restful\Exception\ServerConfigurationException;
use Drupal\restful\Exception\ServiceUnavailableException;
use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\DataInterpreter\ArrayWrapper;
use Drupal\restful\Plugin\resource\DataInterpreter\DataInterpreterArray;
use Drupal\restful\Plugin\resource\Decorators\CacheDecoratedResource;
use Drupal\restful\Plugin\resource\Field\ResourceFieldCollection;
use Drupal\restful\Plugin\resource\Field\ResourceFieldCollectionInterface;
use Drupal\restful\Plugin\resource\Field\ResourceFieldDbColumnInterface;
use Drupal\restful\Plugin\resource\ResourceInterface;

class DataProviderDbQuery extends DataProvider implements DataProviderDbQueryInterface {

  /**
   * The name of the table to query.
   *
   * @var string
   */
  protected $tableName;
  /**
   * The name of the column(s) in the table to be used as the unique key.
   *
   * @var array
   */
  protected $idColumn;

  /**
   * The separator used to divide a key into its table columns when there is
   * more than one column.
   */
  const COLUMN_IDS_SEPARATOR = '::';

  /**
   * Holds the primary field.
   *
   * @var string
   */
  protected $primary;

  /**
   * {@inheritdoc}
   */
  public function __construct(RequestInterface $request, ResourceFieldCollectionInterface $field_definitions, $account, $plugin_id, $resource_path = NULL, array $options = array(), $langcode = NULL) {
    parent::__construct($request, $field_definitions, $account, $plugin_id, $resource_path, $options, $langcode);
    // Validate keys exist in the plugin's "data provider options".
    $required_keys = array(
      'tableName',
      'idColumn',
    );
    $required_callback = function ($required_key) {
      if (!$this->options[$required_key]) {
        throw new ServiceUnavailableException(sprintf('%s is missing "%s" property in the "dataProvider" key of the $plugin', get_class($this), $required_key));
      }
    };
    array_walk($required_keys, $required_callback);
    $this->tableName = $this->options['tableName'];
    $this->idColumn = $this->options['idColumn'];
    $this->primary = empty($this->options['primary']) ? NULL : $this->primary = $this->options['primary'];
  }

  /**
   * {@inheritdoc}
   */
  public function getTableName() {
    return $this->tableName;
  }

  /**
   * {@inheritdoc}
   */
  public function setTableName($table_name) {
    $this->tableName = $table_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getPrimary() {
    return $this->primary;
  }

  /**
   * {@inheritdoc}
   */
  public function setPrimary($primary) {
    $this->primary = $primary;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheFragments($identifier) {
    if (is_array($identifier)) {
      // Like in https://example.org/api/articles/1,2,3.
      $identifier = implode(ResourceInterface::IDS_SEPARATOR, $identifier);
    }
    $fragments = new ArrayCollection(array(
      'resource' => CacheDecoratedResource::serializeKeyValue($this->pluginId, $this->canonicalPath($identifier)),
      'table_name' => $this->getTableName(),
      'column' => implode(',', $this->getIdColumn()),
    ));
    $options = $this->getOptions();
    switch ($options['renderCache']['granularity']) {
      case DRUPAL_CACHE_PER_USER:
        if ($uid = $this->getAccount()->uid) {
          $fragments->set('user_id', (int) $uid);
        }
        break;
      case DRUPAL_CACHE_PER_ROLE:
        $fragments->set('user_role', implode(',', $this->getAccount()->roles));
        break;
    }
    return $fragments;
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return intval($this
      ->getQueryForList()
      ->countQuery()
      ->execute()
      ->fetchField());
  }

  /**
   * {@inheritdoc}
   */
  public function isPrimaryField($field_name) {
    return $this->primary == $field_name;
  }

  /**
   * Get ID column.
   *
   * @return array
   *   An array with the name of the column(s) in the table to be used as the
   *   unique key.
   */
  protected function getIdColumn() {
    return is_array($this->idColumn) ? $this->idColumn : array($this->idColumn);
  }

  /**
   * {@inheritdoc}
   */
  public function create($object) {
    $save = FALSE;
    $original_object = $object;
    $id_columns = $this->getIdColumn();
    $record = array();
    foreach ($this->fieldDefinitions as $public_field_name => $resource_field) {
      /* @var ResourceFieldDbColumnInterface $resource_field */
      if (!$this->methodAccess($resource_field)) {
        // Allow passing the value in the request.
        unset($original_object[$public_field_name]);
        continue;
      }

      $property_name = $resource_field->getProperty();
      // If this is the primary field, skip.
      if ($this->isPrimaryField($property_name)) {
        unset($original_object[$public_field_name]);
        continue;
      }
      if (isset($object[$public_field_name])) {
        $record[$property_name] = $object[$public_field_name];
      }
      unset($original_object[$public_field_name]);
      $save = TRUE;
    }
    // No request was sent.
    if (!$save) {
      throw new BadRequestException('No values were sent with the request.');
    }
    // If the original request is not empty, then illegal values are present.
    if (!empty($original_object)) {
      $error_message = format_plural(count($original_object), 'Property @names is invalid.', 'Property @names are invalid.', array('@names' => implode(', ', array_keys($original_object))));
      throw new BadRequestException($error_message);
    }
    // Once the record is built, write it and view it.
    if (drupal_write_record($this->getTableName(), $record)) {
      // Handle multiple id columns.
      $id_values = array();
      foreach ($id_columns as $id_column) {
        $id_values[$id_column] = $record[$id_column];
      }
      $new_id = implode(self::COLUMN_IDS_SEPARATOR, $id_values);
      return array($this->view($new_id));
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function view($identifier) {
    $query = $this->getQuery();
    foreach ($this->getIdColumn() as $index => $column) {
      $identifier = is_array($identifier) ? $identifier : array($identifier);
      $query->condition($this->getTableName() . '.' . $column, current($this->getColumnFromIds($identifier, $index)));
    }
    $this->addExtraInfoToQuery($query);
    $result = $query
      ->range(0, 1)
      ->execute()
      ->fetch(\PDO::FETCH_OBJ);
    return $this->mapDbRowToPublicFields($result);
  }

  /**
   * {@inheritdoc}
   */
  protected function mapDbRowToPublicFields($row) {
    $resource_field_collection = $this->initResourceFieldCollection($row);

    // Loop over all the defined public fields.
    foreach ($this->fieldDefinitions as $public_field_name => $resource_field) {
      $value = NULL;
      /* @var ResourceFieldDbColumnInterface $resource_field */
      if (!$this->methodAccess($resource_field)) {
        // Allow passing the value in the request.
        continue;
      }
      $resource_field_collection->set($resource_field->id(), $resource_field);
    }
    return $resource_field_collection;
  }

  /**
   * Adds query tags and metadata to the EntityFieldQuery.
   *
   * @param \SelectQuery $query
   *   The query to enhance.
   */
  protected function addExtraInfoToQuery($query) {
    $query->addTag('restful');
    $query->addMetaData('account', $this->getAccount());
    $query->addMetaData('restful_handler', $this);
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $identifiers) {
    // Get a list query with all the sorting and pagination in place.
    $query = $this->getQueryForList();
    if (empty($identifiers)) {
      return array();
    }
    foreach ($this->getIdColumn() as $index => $column) {
      $query->condition($this->getTableName() . '.' . $column, $this->getColumnFromIds($identifiers, $index), 'IN');
    }
    $results = $query->execute();
    $return = array();
    foreach ($results as $result) {
      $return[] = $this->mapDbRowToPublicFields($result);
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  protected function getQueryForList() {
    $query = $this->getQuery();
    $this->queryForListSort($query);
    $this->queryForListFilter($query);
    $this->queryForListPagination($query);
    $this->addExtraInfoToQuery($query);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function update($identifier, $object, $replace = FALSE) {
    // Build the update array.
    $save = FALSE;
    $original_object = $object;
    $id_columns = $this->getIdColumn();
    $record = array();
    foreach ($this->fieldDefinitions as $public_field_name => $resource_field) {
      /* @var ResourceFieldDbColumnInterface $resource_field */
      if (!$this->methodAccess($resource_field)) {
        // Allow passing the value in the request.
        unset($original_object[$public_field_name]);
        continue;
      }
      $property = $resource_field->getProperty();
      // If this is the primary field, skip.
      if ($this->isPrimaryField($property)) {
        continue;
      }
      if (isset($object[$public_field_name])) {
        $record[$property] = $object[$public_field_name];
      }
      // For unset fields on full updates, pass NULL to drupal_write_record().
      elseif ($replace) {
        $record[$property] = NULL;
      }
      unset($original_object[$public_field_name]);
      $save = TRUE;
    }
    // No request was sent.
    if (!$save) {
      throw new BadRequestException('No values were sent with the request.');
    }
    // If the original request is not empty, then illegal values are present.
    if (!empty($original_object)) {
      $error_message = format_plural(count($original_object), 'Property @names is invalid.', 'Property @names are invalid.', array('@names' => implode(', ', array_keys($original_object))));
      throw new BadRequestException($error_message);
    }
    // Add the id column values into the record.
    foreach ($this->getIdColumn() as $index => $column) {
      $record[$column] = current($this->getColumnFromIds(array($identifier), $index));
    }
    // Once the record is built, write it.
    if (!drupal_write_record($this->getTableName(), $record, $id_columns)) {
      throw new ServiceUnavailableException('Record could not be updated to the database.');
    }
    return array($this->view($identifier));
  }

  /**
   * {@inheritdoc}
   */
  public function remove($identifier) {
    // If it's a delete method we will want a 204 response code.
    // Set the HTTP headers.
    $this->setHttpHeader('Status', 204);
    $query = db_delete($this->getTableName());
    foreach ($this->getIdColumn() as $index => $column) {
      $query->condition($column, current($this->getColumnFromIds(array($identifier), $index)));
    }
    $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getIndexIds() {
    $results = $this
      ->getQueryForList()
      ->execute();
    $ids = array();
    foreach ($results as $result) {
      $ids[] = array_map(function ($id_column) use ($result) {
        return $result->{$id_column};
      }, $this->getIdColumn());
    }
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function index() {
    $results = $this
      ->getQueryForList()
      ->execute();
    $return = array();
    foreach ($results as $result) {
      $return[] = $this->mapDbRowToPublicFields($result);
    }
    return $return;
  }

  /**
   * Defines default sort columns if none are provided via the request URL.
   *
   * @return array
   *   Array keyed by the database column name, and the order ('ASC' or 'DESC')
   *   as value.
   */
  protected function defaultSortInfo() {
    $sorts = array();
    foreach ($this->getIdColumn() as $column) {
      if (!$this->fieldDefinitions->get($column)) {
        // Sort by the first ID column that is a public field.
        $sorts[$column] = 'ASC';
        break;
      }
    }
    return $sorts;
  }

  /**
   * Sort the query for list.
   *
   * @param \SelectQuery $query
   *   The query object.
   *
   * @throws BadRequestException
   *
   * @see \RestfulEntityBase::getQueryForList
   */
  protected function queryForListSort(\SelectQuery $query) {
    // Get the sorting options from the request object.
    $sorts = $this->parseRequestForListSort();
    $sorts = $sorts ? $sorts : $this->defaultSortInfo();
    foreach ($sorts as $sort => $direction) {
      /* @var ResourceFieldDbColumnInterface $sort_field */
      if ($sort_field = $this->fieldDefinitions->get($sort)) {
        $query->orderBy($sort_field->getColumnForQuery(), $direction);
      }
    }
  }

  /**
   * Filter the query for list.
   *
   * @param \SelectQuery $query
   *   The query object.
   *
   * @throws BadRequestException
   *
   * @see \RestfulEntityBase::getQueryForList
   */
  protected function queryForListFilter(\SelectQuery $query) {
    foreach ($this->parseRequestForListFilter() as $filter) {
      /* @var ResourceFieldDbColumnInterface $filter_field */
      if (!$filter_field = $this->fieldDefinitions->get($filter['public_field'])) {
        continue;
      }
      $column_name = $filter_field->getColumnForQuery();
      if (in_array(strtoupper($filter['operator'][0]), array('IN', 'NOT IN', 'BETWEEN'))) {
        $query->condition($column_name, $filter['value'], $filter['operator'][0]);
        continue;
      }
      $condition = db_condition($filter['conjunction']);
      for ($index = 0; $index < count($filter['value']); $index++) {
        $condition->condition($column_name, $filter['value'][$index], $filter['operator'][$index]);
      }
      $query->condition($condition);
    }
  }

  /**
   * Set correct page (i.e. range) for the query for list.
   *
   * Determine the page that should be seen. Page 1, is actually offset 0 in the
   * query range.
   *
   * @param \SelectQuery $query
   *   The query object.
   *
   * @throws BadRequestException
   *
   * @see \RestfulEntityBase::getQueryForList
   */
  protected function queryForListPagination(\SelectQuery $query) {
    list($range, $offset) = $this->parseRequestForListPagination();
    $query->range($range, $offset);
  }

  /**
   * Get a basic query object.
   *
   * @return \SelectQuery
   *   A new SelectQuery object for this connection.
   */
  protected function getQuery() {
    $table = $this->getTableName();
    return db_select($table)->fields($table);
  }

  /**
   * Given an array of string ID's return a single column.
   *
   * Strings are divided by the delimiter self::COLUMN_IDS_SEPARATOR.
   *
   * @param array $identifiers
   *   An array of object IDs.
   * @param int $column
   *   0-N Zero indexed
   *
   * @return array
   *   Returns an array at index $column
   */
  protected function getColumnFromIds(array $identifiers, $column = 0) {
    // Get a single column.
    $get_part = function($identifier) use ($column) {
      $parts = explode(static::COLUMN_IDS_SEPARATOR, $identifier);
      if (!isset($parts[$column])) {
        throw new ServerConfigurationException('Invalid ID provided.');
      }
      return $parts[$column];
    };
    return array_map($get_part, $identifiers);
  }

  /**
   * {@inheritdoc}
   */
  protected function initDataInterpreter($identifier) {
    return new DataInterpreterArray($this->getAccount(), new ArrayWrapper((array) $identifier));
  }

}

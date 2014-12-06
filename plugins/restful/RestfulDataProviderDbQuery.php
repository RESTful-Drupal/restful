<?php

/**
 * @file
 * Contains \RestfulDataProviderDbQuery
 */

abstract class RestfulDataProviderDbQuery extends \RestfulBase implements \RestfulDataProviderDbQueryInterface, \RestfulDataProviderInterface {

  /**
   * The name of the table to query.
   *
   * @var string
   */
  protected $tableName;

  /**
   * The name of the column in the table to be used as the unique key.
   *
   * @var string
   */
  protected $idColumn;

  /**
   * Holds the primary field.
   *
   * @var string
   */
  protected $primary;

  /**
   * Get ID column
   *
   * @return string
   *   The name of the column in the table to be used as the unique key.
   */
  public function getIdColumn() {
    return $this->idColumn;
  }

  /**
   * Set the name of the column in the table to be used as the unique key.
   *
   * @param string $id_column
   *   The name of the column in the table to be used as the unique key.
   */
  public function setIdColumn($id_column) {
    $this->idColumn = $id_column;
  }

  /**
   * Get the name of the table to query.
   *
   * @return string
   *   The name of the table to query.
   */
  public function getTableName() {
    return $this->tableName;
  }

  /**
   * Set the name of the table to query.
   *
   * @param string $table_name
   *   The name of the table to query.
   */
  public function setTableName($table_name) {
    $this->tableName = $table_name;
  }

  /**
   * Constructs a RestfulDataProviderDbQuery object.
   *
   * @param array $plugin
   *   Plugin definition.
   * @param RestfulAuthenticationManager $auth_manager
   *   (optional) Injected authentication manager.
   * @param DrupalCacheInterface $cache_controller
   *   (optional) Injected cache backend.
   */
  public function __construct(array $plugin, \RestfulAuthenticationManager $auth_manager = NULL, \DrupalCacheInterface $cache_controller = NULL) {
    parent::__construct($plugin, $auth_manager, $cache_controller);

    // Validate keys exist in the plugin's "data provider options".
    $required_keys = array(
      'table_name',
      'id_column',
    );
    $options = $this->processDataProviderOptions($required_keys);

    $this->tableName = $options['table_name'];
    $this->idColumn = $options['id_column'];
    $this->primary = empty($plugin['primary']) ? NULL : $this->primary = $plugin['primary'];
  }

  /**
   * Defines default sort columns if none are provided via the request URL.
   *
   * @return array
   *   Array keyed by the database column name, and the order ('ASC' or 'DESC') as value.
   */
  public function defaultSortInfo() {
    $sorts = array();
    if (!empty($this->getPublicFields[$this->getIdColumn()])) {
      $sorts[$this->getIdColumn()] = 'ASC';
    }
    return $sorts;
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryForList() {
    $table = $this->getTableName();
    $query = db_select($table)
      ->fields($table);

    $this->queryForListSort($query);
    $this->queryForListFilter($query);
    $this->queryForListPagination($query);
    $this->addExtraInfoToQuery($query);

    return $query;
  }

  /**
   * Sort the query for list.
   *
   * @param \SelectQuery $query
   *   The query object.
   *
   * @throws \RestfulBadRequestException
   *
   * @see \RestfulEntityBase::getQueryForList
   */
  protected function queryForListSort(\SelectQuery $query) {
    $public_fields = $this->getPublicFields();

    // Get the sorting options from the request object.
    $sorts = $this->parseRequestForListSort();

    $sorts = $sorts ? $sorts : $this->defaultSortInfo();

    foreach ($sorts as $sort => $direction) {
      $query->orderBy($public_fields[$sort]['property'], $direction);
    }
  }

  /**
   * Filter the query for list.
   *
   * @param \SelectQuery $query
   *   The query object.
   *
   * @throws \RestfulBadRequestException
   *
   * @see \RestfulEntityBase::getQueryForList
   */
  protected function queryForListFilter(\SelectQuery $query) {
    $public_fields = $this->getPublicFields();
    foreach ($this->parseRequestForListFilter() as $filter) {
      $query->condition($public_fields[$filter['public_field']]['property'], $filter['value'], $filter['operator']);
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
   * @throws \RestfulBadRequestException
   *
   * @see \RestfulEntityBase::getQueryForList
   */
  protected function queryForListPagination(\SelectQuery $query) {
    list($range, $offset) = $this->parseRequestForListPagination();
    $query->range($range, $offset);
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryCount() {
    $table = $this->getTableName();
    $query = db_select($table)
      ->fields($table);

    if ($path = $this->getPath()) {
      $ids = explode(',', $path);
      if (!empty($ids)) {
        $query->condition($table . '.' . $this->getIdColumn(), $ids, 'IN');
      }
    }

    $this->queryForListFilter($query);
    $this->addExtraInfoToQuery($query);
    $query->addTag('restful_count');

    return $query->countQuery();
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalCount() {
    return intval($this
      ->getQueryCount()
      ->execute());
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
   * {@inheritdoc}
   */
  public function viewMultiple(array $ids) {
    $cache_id = array(
      'tb' => $this->getTableName(),
      'cl' => $this->getIdColumn(),
      'id' => implode(',', $ids),
    );
    $cached_data = $this->getRenderedCache($cache_id);
    if (!empty($cached_data->data)) {
      return $cached_data->data;
    }

    // Get a list query with all the sorting and pagination in place.
    $query = $this->getQueryForList();
    if (empty($ids)) {
      return array();
    }
    $query->condition($this->getTableName() . '.' . $this->getIdColumn(), $ids, 'IN');
    $results = $query->execute();

    $return = array();

    foreach ($results as $result) {
      $return[] = $this->mapDbRowToPublicFields($result);
    }

    $this->setRenderedCache($return, $cache_id);
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function view($id) {
    $cache_id = array(
      'tb' => $this->getTableName(),
      'cl' => $this->getIdColumn(),
      'id' => $id,
    );
    $cached_data = $this->getRenderedCache($cache_id);
    if (!empty($cached_data->data)) {
      return $cached_data->data;
    }

    $table = $this->getTableName();
    $query = db_select($table)
      ->fields($table);
    $query->condition($this->getTableName() . '.' . $this->getIdColumn(), $id);

    $this->addExtraInfoToQuery($query);
    $results = $query->execute();


    $return = array();

    foreach ($results as $result) {
      $return[] = $this->mapDbRowToPublicFields($result);
    }

    $this->setRenderedCache($return, $cache_id);
    return $return;
  }

  /**
   * Replace a record by another.
   */
  protected function replace($id) {
    return $this->update($id, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function update($id, $full_replace = FALSE) {
    $query = db_update($this->getTableName());
    $query->condition($this->getIdColumn(), $id);

    // Build the update array.
    $request = $this->getRequest();
    static::cleanRequest($request);
    $public_fields = $this->getPublicFields();
    $fields = array();
    foreach ($public_fields as $public_property => $info) {
      // If this is the primary field, skip.
      if ($this->isPrimaryField($info['property'])) {
        continue;
      }
      // Check if the public property is set in the payload.
      if (!isset($request[$public_property])) {
        if ($full_replace) {
          $fields[$info['property']] = NULL;
        }
      }
      else {
        $fields[$info['property']] = $request[$public_property];
      }
    }
    if (empty($fields)) {
      return $this->view($id);
    }

    // Once the update array is built, execute the query.
    $query->fields($fields)->execute();

    // Clear the rendered cache before calling the view method.
    $this->clearRenderedCache(array(
      'tb' => $this->getTableName(),
      'cl' => $this->getIdColumn(),
      'id' => $id,
    ));
    return $this->view($id, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function create() {
    $query = db_insert($this->getTableName());

    // Build the update array.
    $request = $this->getRequest();
    static::cleanRequest($request);
    $public_fields = $this->getPublicFields();
    $fields = array();
    $passed_id = NULL;
    foreach ($public_fields as $public_property => $info) {
      // Check if the public property is set in the payload.
      if ($info['property'] == $this->getIdColumn()) {
        $passed_id = $request[$public_property];
      }
      if (isset($request[$public_property])) {
        $fields[$info['property']] = $request[$public_property];
      }
    }

    // Once the update array is built, execute the query.
    if ($id = $query->fields($fields)->execute()) {
      return $this->view($id, TRUE);
    }
    // Some times db_insert does not know how to get the ID.
    if ($passed_id) {
      return $this->view($passed_id);
    }
    return;
  }

  /**
   * {@inheritdoc}
   */
  public function remove($id) {
    // If it's a delete method we will want a 204 response code.
    // Set the HTTP headers.
    $this->setHttpHeaders('Status', 204);

    db_delete($this->getTableName())
      ->condition($this->getIdColumn(), $id)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function mapDbRowToPublicFields($row) {
    if ($this->getMethod() == \RestfulInterface::GET) {
      // For read operations cache the result.
      $output = &drupal_static(__CLASS__ . '::' . __FUNCTION__ . '::' . $this->getUniqueId($row));
      if (isset($output)) {
        return $output;
      }
    }
    else {
      // Clear the cache if the request is not GET.
      drupal_static_reset(__CLASS__ . '::' . __FUNCTION__ . '::' . $this->getUniqueId($row));
    }
    // Loop over all the defined public fields.
    foreach ($this->getPublicFields() as $public_field_name => $info) {
      $value = NULL;
      // If there is a callback defined execute it instead of a direct mapping.
      if ($info['callback']) {
        $value = static::executeCallback($info['callback'], array($row));
      }
      // Map row names to public properties.
      elseif ($info['property']) {
        $value = $row->{$info['property']};
      }

      // Execute the process callbacks.
      if ($value && $info['process_callbacks']) {
        foreach ($info['process_callbacks'] as $process_callback) {
          $value = static::executeCallback($process_callback, array($value));
        }
      }

      $output[$public_field_name] = $value;
    }

    return $output;
  }

  /**
   * Returns a unique id for a table record.
   *
   * @param object $row
   *   The database record.
   *
   * @return string
   *   The ID
   */
  public function getUniqueId($row) {
    return $this->getTableName() . '::' . $row->{$this->getIdColumn()};
  }

  /**
   * Checks if the current field is the primary field.
   *
   * @param string $field
   *   The column name to check.
   *
   * @return boolean
   *   TRUE if it is the primary field, FALSE otherwise.
   */
  function isPrimaryField($field) {
    return $this->primary == $field;
  }
}

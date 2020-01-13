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
   * Get ID column
   *
   * @return array
   *   An array with the name of the column(s) in the table to be used as the
   *   unique key.
   */
  public function getIdColumn() {
    return is_array($this->idColumn) ? $this->idColumn : array($this->idColumn);
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
   * @return string
   **/
  public function getPrimary() {
    return $this->primary;
  }

  /**
   * @param string $primary
   **/
  public function setPrimary($primary) {
    $this->primary = $primary;
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
   * @param string $language
   *   (optional) The language to return items in.
   */
  public function __construct(array $plugin, \RestfulAuthenticationManager $auth_manager = NULL, \DrupalCacheInterface $cache_controller = NULL, $language = NULL) {
    parent::__construct($plugin, $auth_manager, $cache_controller, $language);

    // Validate keys exist in the plugin's "data provider options".
    $required_keys = array(
      'table_name',
      'id_column',
    );
    $options = $this->processDataProviderOptions($required_keys);

    $this->tableName = $options['table_name'];
    $this->idColumn = $options['id_column'];
    $this->primary = empty($plugin['data_provider_options']['primary']) ? NULL : $this->primary = $plugin['data_provider_options']['primary'];
  }

  /**
   * Defines default sort columns if none are provided via the request URL.
   *
   * @return array
   *   Array keyed by the database column name, and the order ('ASC' or 'DESC') as value.
   */
  public function defaultSortInfo() {
    $sorts = array();
    foreach ($this->getIdColumn() as $column) {
      if (!empty($this->getPublicFields[$column])) {
        // Sort by the first ID column that is a public field.
        $sorts[$column] = 'ASC';
        break;
      }
    }
    return $sorts;
  }

  /**
   * Get a basic query object.
   *
   * @return SelectQuery
   *   A new SelectQuery object for this connection.
   */
  protected function getQuery() {
    $table = $this->getTableName();
    return db_select($table)->fields($table);
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryForList() {
    $query = $this->getQuery();

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
      $column_name = $this->getPropertyColumnForQuery($public_fields[$sort]);
      $query->orderBy($column_name, $direction);
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
      if (in_array(strtoupper($filter['operator'][0]), array('IN', 'NOT IN', 'BETWEEN'))) {
        $column_name = $this->getPropertyColumnForQuery($public_fields[$filter['public_field']]);
        if (is_array($filter['value']) && empty($filter['value'])) {
          if (strtoupper($filter['operator'][0]) == 'NOT IN') {
            // Skip filtering by an empty value when operator is 'NOT IN',
            // since it throws an SQL error.
            continue;
          }
          // Since Drupal doesn't know how to handle an empty array within a
          // condition we add the `NULL` as an element to the array.
          $filter['value'] = array(NULL);
        }
        $query->condition($column_name, $filter['value'], $filter['operator'][0]);
        continue;
      }
      $condition = db_condition($filter['conjunction']);
      for ($index = 0; $index < count($filter['value']); $index++) {

        $operator = strtoupper($filter['operator'][$index]);
        $value = $filter['value'][$index];

        // Convert CONTAINS and STARTS_WITH operators to mysql's LIKE.
        if (in_array($operator, ['CONTAINS', 'STARTS_WITH'])) {
          $like_prefix = $operator == 'CONTAINS' ? '%' : '';
          $value = $like_prefix . db_like($value) . '%';
          $operator = 'LIKE';
        }

        $column_name = $this->getPropertyColumnForQuery($public_fields[$filter['public_field']]);
        $condition->condition($column_name, $value, $operator);
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
   * @throws \RestfulBadRequestException
   *
   * @see \RestfulEntityBase::getQueryForList
   */
  protected function queryForListPagination(\SelectQuery $query) {
    list($range, $offset) = $this->parseRequestForListPagination();
    $query->range($range, $offset);
  }

  /**
   * Return the column name that should be used for query.
   *
   * As MySql prevents using the column alias on WHERE or ORDER BY, we give
   * implementers a chance to explicitly define the real coloumn for the query.
   *
   * @param $public_field_name
   *   The public field name.
   *
   * @return string
   *   The column name.
   */
  protected function getPropertyColumnForQuery($public_field_name) {
    $public_fields = $this->getPublicFields();
    return !empty($public_fields[$public_field_name['property']]['column_for_query']) ? $public_fields[$public_field_name['property']]['column_for_query'] : $public_field_name['property'];
  }

  /**
   * {@inheritdoc}
   */
  protected function addDefaultValuesToPublicFields(array $public_fields = array()) {
    // Set defaults values.
    $public_fields = parent::addDefaultValuesToPublicFields($public_fields);
    foreach (array_keys($public_fields) as $key) {
      $info = &$public_fields[$key];
      $info += array(
        'column_for_query' => FALSE,
      );
    }

    return $public_fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryCount() {
    $table = $this->getTableName();
    $query = $this->getQuery();

    if ($path = $this->getPath()) {
      $ids = explode(',', $path);
      if (!empty($ids)) {
        foreach ($this->getIdColumn() as $index => $column) {
          $query->condition($table . '.' . $column, $this->getColumnFromIds($ids, $index), 'IN');
        }
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
      ->execute()
      ->fetchField());
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
      'cl' => implode(',', $this->getIdColumn()),
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
    foreach ($this->getIdColumn() as $index => $column) {
      $query->condition($this->getTableName() . '.' . $column, $this->getColumnFromIds($ids, $index), 'IN');
    }
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
  public function view($ids) {
    return $this->viewMultiple(explode(',', $ids));
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
    // Build the update array.
    $request = $this->getRequest();
    static::cleanRequest($request);
    $save = FALSE;
    $original_request = $request;

    $public_fields = $this->getPublicFields();

    $id_columns = $this->getIdColumn();

    $record = array();
    foreach ($public_fields as $public_field_name => $info) {
      // Ignore passthrough public fields.
      if (!empty($info['create_or_update_passthrough'])) {
        unset($original_request[$public_field_name]);
        continue;
      }

      // If this is the primary field, skip.
      if ($this->isPrimaryField($info['property'])) {
        continue;
      }

      if (isset($request[$public_field_name])) {
        $record[$info['property']] = $request[$public_field_name];
      }
      // For unset fields on full updates, pass NULL to drupal_write_record().
      elseif ($full_replace) {
        $record[$info['property']] = NULL;
      }

      unset($original_request[$public_field_name]);
      $save = TRUE;
    }

    // No request was sent.
    if (!$save) {
      throw new \RestfulBadRequestException('No values were sent with the request.');
    }

    // If the original request is not empty, then illegal values are present.
    if (!empty($original_request)) {
      $error_message = format_plural(count($original_request), 'Property @names is invalid.', 'Property @names are invalid.', array('@names' => implode(', ', array_keys($original_request))));
      throw new \RestfulBadRequestException($error_message);
    }

    // Add the id column values into the record.
    foreach ($this->getIdColumn() as $index => $column) {
      $record[$column] = current($this->getColumnFromIds(array($id), $index));
    }

    // Once the record is built, write it.
    if (!drupal_write_record($this->getTableName(), $record, $id_columns)) {
      throw new \RestfulServiceUnavailable('Record could not be updated to the database.');
    }

    // Clear the rendered cache before calling the view method.
    $this->clearRenderedCache(array(
      'tb' => $this->getTableName(),
      'cl' => implode(',', $this->getIdColumn()),
      'id' => $id,
    ));

    return $this->view($id);
  }

  /**
   * {@inheritdoc}
   */
  public function create() {
    $request = $this->getRequest();
    static::cleanRequest($request);
    $save = FALSE;
    $original_request = $request;

    $public_fields = $this->getPublicFields();
    $id_columns = $this->getIdColumn();


    $record = array();
    foreach ($public_fields as $public_field_name => $info) {
      // Ignore passthrough public fields.
      if (!empty($info['create_or_update_passthrough'])) {
        unset($original_request[$public_field_name]);
        continue;
      }

      // If this is the primary field, skip.
      if ($this->isPrimaryField($info['property'])) {
        unset($original_request[$public_field_name]);
        continue;
      }

      if (isset($request[$public_field_name])) {
        $record[$info['property']] = $request[$public_field_name];
      }

      unset($original_request[$public_field_name]);
      $save = TRUE;
    }

    // No request was sent.
    if (!$save) {
      throw new \RestfulBadRequestException('No values were sent with the request.');
    }

    // If the original request is not empty, then illegal values are present.
    if (!empty($original_request)) {
      $error_message = format_plural(count($original_request), 'Property @names is invalid.', 'Property @names are invalid.', array('@names' => implode(', ', array_keys($original_request))));
      throw new \RestfulBadRequestException($error_message);
    }

    // Once the record is built, write it and view it.
    if (drupal_write_record($this->getTableName(), $record)) {
      // Handle multiple id columns.
      $id_values = array();
      foreach ($id_columns as $id_column) {
        $id_values[$id_column] = $record[$id_column];
      }
      $id = implode(self::COLUMN_IDS_SEPARATOR, $id_values);

      return $this->view($id);
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

    $query = db_delete($this->getTableName());
    foreach ($this->getIdColumn() as $index => $column) {
      $query->condition($column, current($this->getColumnFromIds(array($id), $index)));
    }

    $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function mapDbRowToPublicFields($row) {
    if ($this->getMethod() == \RestfulInterface::GET) {
      // For read operations cache the result.
      $output = $this->staticCache->get(__CLASS__ . '::' . __FUNCTION__ . '::' . $this->getUniqueId($row));
      if (isset($output)) {
        return $output;
      }
    }
    else {
      // Clear the cache if the request is not GET.
      $this->staticCache->clear(__CLASS__ . '::' . __FUNCTION__ . '::' . $this->getUniqueId($row));
    }
    $output = array();
    // Loop over all the defined public fields.
    foreach ($this->getPublicFields() as $public_field_name => $info) {
      $value = NULL;

      if ($info['create_or_update_passthrough']) {
        // The public field is a dummy one, meant only for passing data upon
        // create or update.
        continue;
      }

      // If there is a callback defined execute it instead of a direct mapping.
      if ($info['callback']) {
        $value = static::executeCallback($info['callback'], array($row));
      }
      // Map row names to public properties.
      elseif ($info['property']) {
        $value = $row->{$info['property']};
      }

      // Execute the process callbacks.
      if (isset($value) && $info['process_callbacks']) {
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
    $keys = array($this->getTableName());

    foreach ($this->getIdColumn() as $column) {
      $keys[] = $row->{$column};
    }

    return implode(self::COLUMN_IDS_SEPARATOR, $keys);
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

  /**
   * Given an array of string ID's return a single column. for example:
   *
   * Strings are divided by the delimiter self::COLUMN_IDS_SEPARATOR.
   *
   * @param array $ids
   *   An array of object IDs.
   * @param int $column
   *   0-N Zero indexed
   *
   * @return Array
   *   Returns an array at index $column
   */
  protected function getColumnFromIds(array $ids, $column = 0) {
    // Get a single column.
    return array_map(function($id) use ($column) {
      $parts = explode(RestfulDataProviderDbQuery::COLUMN_IDS_SEPARATOR, $id);
      if (!isset($parts[$column])) {
        throw new \RestfulServerConfigurationException('Invalid ID provided.');
      }
      return $parts[$column];
    }, $ids);
  }
}

<?php

/**
 * @file
 * Contains \RestfulQuery
 */

abstract class RestfulQuery extends \RestfulBase implements \RestfulQueryInterface, \RestfulDataProviderInterface {

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
   * @param string $idColumn
   *   The name of the column in the table to be used as the unique key.
   */
  public function setIdColumn($idColumn) {
    $this->idColumn = $idColumn;
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
   * @param string $tableName
   *   The name of the table to query.
   */
  public function setTableName($tableName) {
    $this->tableName = $tableName;
  }

  /**
   * Constructs a RestfulQuery object.
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
    $this->tableName = $plugin['table_name'];
    $this->idColumn = $plugin['id_column'];
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
    if (empty($sorts)) {
      $sorts[$this->getIdColumn()] = 'ASC';
    }

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
    if (!$this->isListRequest()) {
      // Not a list request, so we don't need to filter.
      // We explicitly check this, as this function might be called from a
      // formatter plugin, after RESTful's error handling has finished, and an
      // invalid key might be passed.
      return;
    }
    $request = $this->getRequest();
    if (empty($request['filter'])) {
      // No filtering is needed.
      return;
    }

    $public_fields = $this->getPublicFields();

    foreach ($request['filter'] as $property => $value) {
      if (empty($public_fields[$property])) {
        throw new RestfulBadRequestException(format_string('The filter @filter is not allowed for this path.', array('@filter' => $property)));
      }

      if (!is_array($value)) {
        // Request uses the shorthand form for filter. For example
        // filter[foo]=bar would be converted to filter[foo][value] = bar.
        $value = array('value' => $value);
      }

      // Set default operator.
      $value += array('operator' => '=');

      $query->condition($public_fields[$property]['property'], $value['value'], $value['operator']);
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
  protected function addExtraInfoToQuery(\SelectQuery $query) {
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

    // TODO: Right now render cache only works for Entity based resources.

    $return = array();

    foreach ($results as $result) {
      $return[] = $this->map($result);
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $ids) {
    // Get a list query with all the sorting and pagination in place.
    $query = $this->getQueryForList();
    if (empty($ids)) {
      return array();
    }
    $query->condition($this->getTableName() . '.' . $this->getIdColumn(), $ids, 'IN');
    $results = $query->execute();

    // TODO: Right now render cache only works for Entity based resources.

    $return = array();

    foreach ($results as $result) {
      $return[] = $this->map($result);
    }

    return $return;

  }

  /**
   * {@inheritdoc}
   */
  public function view($id) {
    $table = $this->getTableName();
    $query = db_select($table)
      ->fields($table);
    $query->condition($this->getTableName() . '.' . $this->getIdColumn(), $id);

    $this->addExtraInfoToQuery($query);
    $results = $query->execute();

    // TODO: Right now render cache only works for Entity based resources.

    $return = array();

    foreach ($results as $result) {
      $return[] = $this->map($result);
    }

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
    $query->condition($this->getTableName() . '.' . $this->getIdColumn(), $id);

    // Build the update array.
    $request = $this->getRequest();
    static::cleanRequest($request);
    $public_fields = $this->getPublicFields();
    $fields = array();
    foreach ($public_fields as $public_property => $info) {
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
    return $this->view($id);
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
      return $this->view($id);
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
    db_delete($this->getTableName())
      ->condition($this->getIdColumn(), $id)
      ->execute();

    // Set the HTTP headers.
    $this->setHttpHeaders('Status', 204);
  }

  /**
   * {@inheritdoc}
   */
  public function map($row) {
    $output = &drupal_static(__CLASS__ . '::' . __FUNCTION__ . '::' . $this->getUniqueId($row));
    if (isset($output)) {
      return $output;
    }
    // Loop over all the defined public fields.
    foreach ($this->getPublicFields() as $public_property_name => $info) {
      $value = NULL;
      // If there is a callback defined execute it instead of a direct mapping.
      if ($info['callback']) {
        $value = static::executeCallback($info['callback'], array($row));
      }
      // Map row names to public properties.
      elseif ($info['property']) {
        $value = $row->{$info['property']};
      }
      else {
        continue;
      }

      // Execute the process callbacks.
      if ($value && $info['process_callbacks']) {
        foreach ($info['process_callbacks'] as $process_callback) {
          $value = static::executeCallback($process_callback, array($value));
        }
      }

      $output[$public_property_name] = $value;
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
   * Helper method to unserialize an object.
   *
   * @param string $data
   *   The serialized data.
   *
   * @return mixed
   *   The unserialized data.
   */
  public static function unserializeData($data) {
    return unserialize($data);
  }

}

<?php

/**
 * @file
 * Contains \RestfulDataProviderEFQ
 */

abstract class RestfulDataProviderEFQ extends \RestfulBase implements \RestfulDataProviderEFQInterface, \RestfulDataProviderInterface {

  /**
   * The entity type.
   *
   * @var string
   */
  protected $entityType;

  /**
   * The bundle.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The bundle.
   *
   * @var string
   */
  protected $EFQClass = '\EntityFieldQuery';

  /**
   * Getter for $bundle.
   *
   * @return string
   */
  public function getBundle() {
    return $this->bundle;
  }

  /**
   * Getter for $entityType.
   *
   * @return string
   */
  public function getEntityType() {
    return $this->entityType;
  }

  /**
   * Get the entity info for the current entity the endpoint handling.
   *
   * @param null $type
   *   The entity type. Optional.
   * @return array
   *   The entity info.
   */
  public function getEntityInfo($type = NULL) {
    return entity_get_info($type ? $type : $this->getEntityType());
  }

  /**
   * Constructs a RestfulDataProviderEFQ object.
   *
   * @param array $plugin
   *   Plugin definition.
   * @param RestfulAuthenticationManager $auth_manager
   *   (optional) Injected authentication manager.
   * @param DrupalCacheInterface $cache_controller
   *   (optional) Injected cache backend.
   * @param string $language
   *   (optional) The language to return items in.
   *
   * @throws RestfulServerConfigurationException
   */
  public function __construct(array $plugin, \RestfulAuthenticationManager $auth_manager = NULL, \DrupalCacheInterface $cache_controller = NULL, $language = NULL) {
    parent::__construct($plugin, $auth_manager, $cache_controller, $language);
    $this->entityType = $plugin['entity_type'];
    $this->bundle = $plugin['bundle'];

    // Allow providing an alternative to \EntityFieldQuery.
    $data_provider_options = $this->getPluginKey('data_provider_options');
    if (!empty($data_provider_options['efq_class'])) {
      if (!is_subclass_of($data_provider_options['efq_class'], '\EntityFieldQuery')) {
        throw new \RestfulServerConfigurationException(format_string('The provided class @class does not extend from \EntityFieldQuery.', array(
          '@class' => $data_provider_options['efq_class'],
        )));
      }
      $this->EFQClass = $data_provider_options['efq_class'];
    }
  }

  /**
   * Defines default sort fields if none are provided via the request URL.
   *
   * @return array
   *   Array keyed by the public field name, and the order ('ASC' or 'DESC') as value.
   */
  public function defaultSortInfo() {
    return array('id' => 'ASC');
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryForList() {
    $entity_type = $this->getEntityType();
    $query = $this->getEntityFieldQuery();
    if ($path = $this->getPath()) {
      $ids = explode(',', $path);
      if (!empty($ids)) {
        $query->entityCondition('entity_id', $ids, 'IN');
      }
    }

    $this->queryForListSort($query);
    $this->queryForListFilter($query);
    $this->queryForListPagination($query);
    $this->addExtraInfoToQuery($query);

    return $query;
  }

  /**
   * Sort the query for list.
   *
   * @param \EntityFieldQuery $query
   *   The query object.
   *
   * @throws \RestfulBadRequestException
   *
   * @see \RestfulEntityBase::getQueryForList
   */
  protected function queryForListSort(\EntityFieldQuery $query) {
    $public_fields = $this->getPublicFields();

    // Get the sorting options from the request object.
    $sorts = $this->parseRequestForListSort();

    $sorts = $sorts ? $sorts : $this->defaultSortInfo();

    foreach ($sorts as $public_field_name => $direction) {
      // Determine if sorting is by field or property.
      if (!$property_name = $public_fields[$public_field_name]['property']) {
        throw new \RestfulBadRequestException('The current sort selection does not map to any entity property or Field API field.');
      }
      if (field_info_field($property_name)) {
        $query->fieldOrderBy($public_fields[$public_field_name]['property'], $public_fields[$public_field_name]['column'], $direction);
      }
      else {
        $column = $this->getColumnFromProperty($property_name);
        $query->propertyOrderBy($column, $direction);
      }
    }
  }

  /**
   * Filter the query for list.
   *
   * @param \EntityFieldQuery $query
   *   The query object.
   *
   * @throws \RestfulBadRequestException
   *
   * @see \RestfulEntityBase::getQueryForList
   */
  protected function queryForListFilter(\EntityFieldQuery $query) {
    $public_fields = $this->getPublicFields();
    foreach ($this->parseRequestForListFilter() as $filter) {
      // Determine if filtering is by field or property.
      if (!$property_name = $public_fields[$filter['public_field']]['property']) {
        throw new \RestfulBadRequestException('The current filter selection does not map to any entity property or Field API field.');
      }
      if (field_info_field($property_name)) {
        if (in_array(strtoupper($filter['operator'][0]), array('IN', 'NOT IN', 'BETWEEN'))) {
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
          $query->fieldCondition($public_fields[$filter['public_field']]['property'], $public_fields[$filter['public_field']]['column'], $filter['value'], $filter['operator'][0]);
          continue;
        }
        for ($index = 0; $index < count($filter['value']); $index++) {
          $query->fieldCondition($public_fields[$filter['public_field']]['property'], $public_fields[$filter['public_field']]['column'], $filter['value'][$index], $filter['operator'][$index]);
        }
      }
      else {
        $column = $this->getColumnFromProperty($property_name);
        if (in_array(strtoupper($filter['operator'][0]), array('IN', 'NOT IN', 'BETWEEN'))) {
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
          $query->propertyCondition($column, $filter['value'], $filter['operator'][0]);
          continue;
        }
        for ($index = 0; $index < count($filter['value']); $index++) {
          $query->propertyCondition($column, $filter['value'][$index], $filter['operator'][$index]);
        }
      }
    }
  }

  /**
   * Get the DB column name from a property.
   *
   * The "property" defined in the public field is actually the property
   * of the entity metadata wrapper. Sometimes that property can be a
   * different name than the column in the DB. For example, for nodes the
   * "uid" property is mapped in entity metadata wrapper as "author", so
   * we make sure to get the real column name.
   *
   * @param string $property_name
   *   The property name.
   *
   * @return string
   *   The column name.
   */
  protected function getColumnFromProperty($property_name) {
    $property_info = entity_get_property_info($this->getEntityType());
    return $property_info['properties'][$property_name]['schema field'];
  }

  /**
   * Overrides \RestfulBase::isValidOperatorsForFilter().
   */
  protected static function isValidOperatorsForFilter(array $operators) {
    $allowed_operators = array(
      '=',
      '>',
      '<',
      '>=',
      '<=',
      '<>',
      '!=',
      'BETWEEN',
      'CONTAINS',
      'IN',
      'LIKE',
      'NOT IN',
      'STARTS_WITH',
    );

    foreach ($operators as $operator) {
      if (!in_array($operator, $allowed_operators)) {
        throw new \RestfulBadRequestException(format_string('Operator "@operator" is not allowed for filtering on this resource. Allowed operators are: !allowed', array(
          '@operator' => $operator,
          '!allowed' => implode(', ', $allowed_operators),
        )));
      }
    }
  }

  /**
   * Overrides \RestfulBase::isValidConjuctionForFilter().
   */
  protected static function isValidConjunctionForFilter($conjunction) {
    $allowed_conjunctions = array(
      'AND',
    );

    if (!in_array(strtoupper($conjunction), $allowed_conjunctions)) {
      throw new \RestfulBadRequestException(format_string('Conjunction "@conjunction" is not allowed for filtering on this resource. Allowed conjunctions are: !allowed', array(
        '@conjunction' => $conjunction,
        '!allowed' => implode(', ', $allowed_conjunctions),
      )));
    }
  }

  /**
   * Set correct page (i.e. range) for the query for list.
   *
   * Determine the page that should be seen. Page 1, is actually offset 0 in the
   * query range.
   *
   * @param \EntityFieldQuery $query
   *   The query object.
   *
   * @throws \RestfulBadRequestException
   *
   * @see \RestfulEntityBase::getQueryForList
   */
  protected function queryForListPagination(\EntityFieldQuery $query) {
    list($offset, $range) = $this->parseRequestForListPagination();
    $query->range($offset, $range);
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryCount() {
    $query = $this->getEntityFieldQuery();
    if ($path = $this->getPath()) {
      $ids = explode(',', $path);
      $query->entityCondition('entity_id', $ids, 'IN');
    }

    $this->queryForListFilter($query);

    $this->addExtraInfoToQuery($query);
    $query->addTag('restful_count');

    return $query->count();
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
   * @param \EntityFieldQuery $query
   *   The query to enhance.
   */
  protected function addExtraInfoToQuery($query) {
    parent::addExtraInfoToQuery($query);
    $entity_type = $this->getEntityType();
    // The only time you need to add the access tags to a EFQ is when you don't
    // have fieldConditions.
    if (empty($query->fieldConditions)) {
      // Add a generic entity access tag to the query.
      $query->addTag($entity_type . '_access');
    }
    $query->addMetaData('restful_handler', $this);
  }

  /**
   * {@inheritdoc}
   */
  public function index() {
    // Defer the actual implementation to \RestfulEntityBase.
    return $this->getList();
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $ids) {
    // Defer the actual implementation to \RestfulEntityBase.
    return $this->viewEntities(implode(',', $ids));
  }

  /**
   * {@inheritdoc}
   */
  public function view($id) {
    // Defer the actual implementation to \RestfulEntityBase.
    return $this->viewEntity($id);
  }

  /**
   * {@inheritdoc}
   */
  public function update($id, $full_replace = FALSE) {
    // Defer the actual implementation to \RestfulEntityBase.
    return $this->updateEntity($id, $full_replace);
  }

  /**
   * {@inheritdoc}
   */
  public function create() {
    // Defer the actual implementation to \RestfulEntityBase.
    return $this->createEntity();
  }

  /**
   * {@inheritdoc}
   */
  public function remove($id) {
    // Defer the actual implementation to \RestfulEntityBase.
    $this->deleteEntity($id);
  }

  /**
   * Get a list of entities.
   *
   * @return array
   *   Array of entities, as passed to RestfulEntityBase::viewEntity().
   *
   * @throws RestfulBadRequestException
   */
  abstract public function getList();

  /**
   * View an entity.
   *
   * @param $id
   *   The ID to load the entity.
   *
   * @return array
   *   Array with the public fields populated.
   *
   * @throws Exception
   */
  abstract public function viewEntity($id);

  /**
   * Get a list of entities based on a list of IDs.
   *
   * @param string $ids_string
   *   Coma separated list of ids.
   *
   * @return array
   *   Array of entities, as passed to RestfulEntityBase::viewEntity().
   *
   * @throws RestfulBadRequestException
   */
  abstract public function viewEntities($ids_string);

  /**
   * Create a new entity.
   *
   * @return array
   *   Array with the output of the new entity, passed to
   *   RestfulEntityInterface::viewEntity().
   *
   * @throws RestfulForbiddenException
   */
  abstract public function createEntity();

  /**
   * Update an entity.
   *
   * @param $id
   *   The ID to load the entity.
   * @param bool $null_missing_fields
   *   Determine if properties that are missing form the request array should
   *   be treated as NULL, or should be skipped. Defaults to FALSE, which will
   *   skip missing the fields to NULL.
   *
   * @return array
   *   Array with the output of the new entity, passed to
   *   RestfulEntityInterface::viewEntity().
   */
  abstract protected function updateEntity($id, $null_missing_fields = FALSE);

  /**
   * Delete an entity using DELETE.
   *
   * No result is returned, just the HTTP header is set to 204.
   *
   * @param $id
   *   The ID to load the entity.
   */
  abstract public function deleteEntity($id);

  /**
   * Initialize an EntityFieldQuery (or extending class).
   *
   * @return \EntityFieldQuery
   *   The initialized query with the basics filled in.
   */
  protected function getEntityFieldQuery() {
    $query = $this->EFQObject();
    $entity_type = $this->getEntityType();
    $query->entityCondition('entity_type', $entity_type);
    $entity_info = $this->getEntityInfo();
    if ($this->getBundle() && $entity_info['entity keys']['bundle']) {
      $query->entityCondition('bundle', $this->getBundle());
    }
    return $query;
  }

  /**
   * Gets a EFQ object.
   *
   * @return \EntityFieldQuery
   *   The object that inherits from \EntityFieldQuery.
   */
  protected function EFQObject() {
    $efq_class = $this->EFQClass;
    return new $efq_class();
  }

}

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
   * Constructs a RestfulDataProviderEFQ object.
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
    $this->entityType = $plugin['entity_type'];
    $this->bundle = $plugin['bundle'];
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
    $entity_info = entity_get_info($entity_type);
    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', $this->getEntityType());

    if ($this->bundle && $entity_info['entity keys']['bundle']) {
      $query->entityCondition('bundle', $this->getBundle());
    }
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

    foreach ($sorts as $sort => $direction) {
      // Determine if sorting is by field or property.
      if (empty($public_fields[$sort]['column'])) {
        $query->propertyOrderBy($public_fields[$sort]['property'], $direction);
      }
      else {
        $query->fieldOrderBy($public_fields[$sort]['property'], $public_fields[$sort]['column'], $direction);
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
      // Determine if sorting is by field or property.
      if (empty($public_fields[$filter['public_field']]['column'])) {
        $query->propertyCondition($public_fields[$filter['public_field']]['property'], $filter['value'], $filter['operator']);
      }
      else {
        $query->fieldCondition($public_fields[$filter['public_field']]['property'], $public_fields[$filter['public_field']]['column'], $filter['value'], $filter['operator']);
      }
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
    $entity_type = $this->getEntityType();
    $entity_info = entity_get_info($entity_type);
    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', $this->getEntityType());

    if ($this->bundle && $entity_info['entity keys']['bundle']) {
      $query->entityCondition('bundle', $this->getBundle());
    }
    if ($path = $this->getPath()) {
      $ids = explode(',', $path);
      $query->entityCondition('entity_id', $ids, 'IN');
    }

    $this->addExtraInfoToQuery($query);
    $query->addTag('restful_count');

    $this->queryForListFilter($query);

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
    // Add a generic entity access tag to the query.
    $query->addTag($entity_type . '_access');
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
   * @param int $entity_id
   *   The entity ID.
   *
   * @return array
   *   Array with the public fields populated.
   *
   * @throws Exception
   */
  abstract public function viewEntity($entity_id);

  /**
   * Get a list of entities based on a list of IDs.
   *
   * @param string $entity_ids_string
   *   Coma separated list of entities.
   *
   * @return array
   *   Array of entities, as passed to RestfulEntityBase::viewEntity().
   *
   * @throws RestfulBadRequestException
   */
  abstract public function viewEntities($entity_ids_string);

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
   * @param $entity_id
   *   The entity ID.
   * @param bool $null_missing_fields
   *   Determine if properties that are missing form the request array should
   *   be treated as NULL, or should be skipped. Defaults to FALSE, which will
   *   skip missing the fields to NULL.
   *
   * @return array
   *   Array with the output of the new entity, passed to
   *   RestfulEntityInterface::viewEntity().
   */
  abstract protected function updateEntity($entity_id, $null_missing_fields = FALSE);

  /**
   * Delete an entity using DELETE.
   *
   * No result is returned, just the HTTP header is set to 204.
   *
   * @param $entity_id
   *   The entity ID.
   */
  abstract public function deleteEntity($entity_id);

}

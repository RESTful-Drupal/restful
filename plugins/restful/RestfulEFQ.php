<?php

/**
 * @file
 * Contains \RestfulEFQ
 */

abstract class RestfulEFQ extends \RestfulBase implements \RestfulEFQInterface, \RestfulDataProviderInterface {

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
   * Constructs a RestfulEFQ object.
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
    $request = $this->getRequest();
    $public_fields = $this->getPublicFields();

    $sorts = array();
    if (!empty($request['sort'])) {
      foreach (explode(',', $request['sort']) as $sort) {
        $direction = $sort[0] == '-' ? 'DESC' : 'ASC';
        $sort = str_replace('-', '', $sort);
        // Check the sort is on a legal key.
        if (empty($public_fields[$sort])) {
          throw new RestfulBadRequestException(format_string('The sort @sort is not allowed for this path.', array('@sort' => $sort)));
        }

        $sorts[$sort] = $direction;
      }
    }
    else {
      // Some endpoints like 'token_auth' don't have an id public field. In that
      // case, skip the default sorting.
      if (!empty($public_fields['id'])) {
        // Sort by default using the entity ID.
        $sorts['id'] = 'ASC';
      }
    }

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

      // Determine if sorting is by field or property.
      if (empty($public_fields[$property]['column'])) {
        $query->propertyCondition($public_fields[$property]['property'], $value['value'], $value['operator']);
      }
      else {
        $query->fieldCondition($public_fields[$property]['property'], $public_fields[$property]['column'], $value['value'], $value['operator']);
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
    $request = $this->getRequest();
    $page = isset($request['page']) ? $request['page'] : 1;

    if (!ctype_digit((string)$page) || $page < 1) {
      throw new \RestfulBadRequestException('"Page" property should be numeric and equal or higher than 1.');
    }

    $range = $this->getRange();
    $offset = ($page - 1) * $range;
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
  protected function addExtraInfoToQuery(\EntityFieldQuery $query) {
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

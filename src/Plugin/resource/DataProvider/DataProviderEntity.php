<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\DataProvider\DataProviderEntity.
 */

namespace Drupal\restful\Plugin\resource\DataProvider;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Doctrine\Common\Collections\ArrayCollection;
use Drupal\restful\Exception\ForbiddenException;
use Drupal\restful\Exception\InternalServerErrorException;
use Drupal\restful\Exception\ServerConfigurationException;
use Drupal\restful\Exception\InaccessibleRecordException;
use Drupal\restful\Http\Request;
use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\Decorators\CacheDecoratedResource;
use Drupal\restful\Plugin\resource\Field\ResourceFieldEntityAlterableInterface;
use Drupal\restful\Plugin\resource\Field\ResourceFieldResourceInterface;
use Drupal\restful\Plugin\resource\ResourceEntity;
use Drupal\restful\Plugin\resource\DataInterpreter\DataInterpreterEMW;
use Drupal\restful\Plugin\resource\DataInterpreter\DataInterpreterInterface;
use Drupal\restful\Plugin\resource\Field\ResourceFieldCollectionInterface;
use Drupal\restful\Plugin\resource\Field\ResourceFieldEntity;
use Drupal\restful\Exception\BadRequestException;
use Drupal\restful\Exception\UnprocessableEntityException;
use Drupal\restful\Plugin\resource\Field\ResourceFieldInterface;
use Drupal\restful\Plugin\resource\Resource;
use Drupal\restful\Plugin\resource\ResourceInterface;
use Drupal\restful\Util\ExplorableDecoratorInterface;
use Drupal\restful\Util\RelationalFilter;
use Drupal\restful\Util\RelationalFilterInterface;
use Drupal\entity_validator\ValidatorPluginManager;

/**
 * Class DataProviderEntity.
 *
 * @package Drupal\restful\Plugin\resource\DataProvider
 */
class DataProviderEntity extends DataProvider implements DataProviderEntityInterface {

  /**
   * The entity type.
   *
   * @var string
   */
  protected $entityType;

  /**
   * The entity bundles.
   *
   * @var array
   */
  protected $bundles = array();

  /**
   * The entity field query class.
   *
   * @var string
   */
  protected $EFQClass = '\Drupal\restful\Util\EntityFieldQuery';

  /**
   * Constructor.
   *
   * @param RequestInterface $request
   *   The request.
   * @param ResourceFieldCollectionInterface $field_definitions
   *   The field definitions.
   * @param object $account
   *   The account object.
   * @param string $plugin_id
   *   The resource ID.
   * @param string $resource_path
   *   The resource path.
   * @param array $options
   *   The plugin definition options for the data provider.
   * @param string $langcode
   *   The entity language code.
   *
   * @throws InternalServerErrorException
   *   If there is no entity type.
   * @throws ServerConfigurationException
   *   If the field mappings are not for entities.
   */
  public function __construct(RequestInterface $request, ResourceFieldCollectionInterface $field_definitions, $account, $plugin_id, $resource_path, array $options, $langcode = NULL) {
    parent::__construct($request, $field_definitions, $account, $plugin_id, $resource_path, $options, $langcode);
    if (empty($options['entityType'])) {
      // Entity type is mandatory.
      throw new InternalServerErrorException('The entity type was not provided.');
    }
    $this->entityType = $options['entityType'];

    $options += array('bundles' => array());
    if ($options['bundles']) {
      $this->bundles = $options['bundles'];
    }
    elseif ($options['bundles'] !== FALSE) {
      // If no bundles are passed, then assume all the bundles of the entity
      // type.
      $entity_info = entity_get_info($this->entityType);
      $this->bundles = !empty($entity_info['bundles']) ? array_keys($entity_info['bundles']) : $entity_info['type'];
    }

    if (isset($options['EFQClass'])) {
      $this->EFQClass = $options['EFQClass'];
    }

    $this->setResourcePath($resource_path);
    if (empty($this->options['urlParams'])) {
      $this->options['urlParams'] = array(
        'filter' => TRUE,
        'sort' => TRUE,
        'fields' => TRUE,
        'loadByFieldName' => TRUE,
      );
    }
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
      'entity' => CacheDecoratedResource::serializeKeyValue($this->entityType, $this->getEntityIdByFieldId($identifier)),
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
   * Defines default sort fields if none are provided via the request URL.
   *
   * @return array
   *   Array keyed by the public field name, and the order ('ASC' or 'DESC') as
   *   value.
   */
  protected function defaultSortInfo() {
    return empty($this->options['sort']) ? array('id' => 'ASC') : $this->options['sort'];
  }

  /**
   * {@inheritdoc}
   */
  public function getIndexIds() {
    $result = $this
      ->getQueryForList()
      ->execute();

    if (empty($result[$this->entityType])) {
      return array();
    }

    $entity_ids = array_keys($result[$this->entityType]);
    if (empty($this->options['idField'])) {
      return $entity_ids;
    }

    // Get the list of IDs.
    $resource_field = $this->fieldDefinitions->get($this->options['idField']);
    $ids = array();
    foreach ($entity_ids as $entity_id) {
      $interpreter = new DataInterpreterEMW($this->getAccount(), new \EntityDrupalWrapper($this->entityType, $entity_id));
      $ids[] = $resource_field->value($interpreter);
    }

    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    $query = $this->getQueryCount();
    return intval($query->execute());
  }

  /**
   * {@inheritdoc}
   */
  public function create($object) {
    $this->validateBody($object);
    $entity_info = $this->getEntityInfo();
    $bundle_key = $entity_info['entity keys']['bundle'];
    // TODO: figure out how to derive the bundle when posting to a resource with
    // multiple bundles.
    $bundle = reset($this->bundles);
    $values = $bundle_key ? array($bundle_key => $bundle) : array();

    $entity = entity_create($this->entityType, $values);

    if ($this->checkEntityAccess('create', $this->entityType, $entity) === FALSE) {
      // User does not have access to create entity.
      throw new ForbiddenException('You do not have access to create a new resource.');
    }

    /* @var \EntityDrupalWrapper $wrapper */
    $wrapper = entity_metadata_wrapper($this->entityType, $entity);

    $this->setPropertyValues($wrapper, $object, TRUE);

    // The access calls use the request method. Fake the view to be a GET.
    $old_request = $this->getRequest();
    $this->getRequest()->setMethod(RequestInterface::METHOD_GET);
    $output = array($this->view($wrapper->getIdentifier()));
    // Put the original request back to a POST.
    $this->request = $old_request;

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function view($identifier) {
    $entity_id = $this->getEntityIdByFieldId($identifier);

    if (!$this->isValidEntity('view', $entity_id)) {
      throw new InaccessibleRecordException(sprintf('The current user cannot access entity "%s".', $entity_id));
    }
    $field_collection = $this->initResourceFieldCollection($identifier);
    // Defer sparse fieldsets to the formatter. That way we can minimize cache
    // fragmentation because we have a unique cache record for all the sparse
    // fieldsets combinations.
    // When caching is enabled and we get a cache MISS we want to generate
    // output for the cache entry for the whole entity. That way we can use that
    // cache record independently of the sparse fieldset.
    // On the other hand, if cache is not enabled we don't want to output for
    // the whole entity, only the bits that we are going to need. For
    // performance reasons.
    $input = $this->getRequest()->getParsedInput();
    $limit_fields = !empty($input['fields']) ? explode(',', $input['fields']) : array();
    $field_collection->setLimitFields($limit_fields);

    foreach ($this->fieldDefinitions as $resource_field) {
      // Create an empty field collection and populate it with the appropriate
      // resource fields.
      /* @var \Drupal\restful\Plugin\resource\Field\ResourceFieldEntityInterface $resource_field */

      if (!$this->methodAccess($resource_field) || !$resource_field->access('view', $field_collection->getInterpreter())) {
        // The field does not apply to the current method or has denied access.
        continue;
      }

      $field_collection->set($resource_field->id(), $resource_field);
    }

    return $field_collection;
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $identifiers) {
    $return = array();
    // If no IDs were requested, we should not throw an exception in case an
    // entity is un-accessible by the user.
    foreach ($identifiers as $identifier) {
      try {
        $row = $this->view($identifier);
      }
      catch (InaccessibleRecordException $e) {
        $row = NULL;
      }
      $return[] = $row;
    }

    return array_values(array_filter($return));
  }

  /**
   * {@inheritdoc}
   */
  public function update($identifier, $object, $replace = FALSE) {
    $this->validateBody($object);
    $entity_id = $this->getEntityIdByFieldId($identifier);
    $this->isValidEntity('update', $entity_id);

    /* @var \EntityDrupalWrapper $wrapper */
    $wrapper = entity_metadata_wrapper($this->entityType, $entity_id);

    $this->setPropertyValues($wrapper, $object, $replace);

    // Set the HTTP headers.
    $this->setHttpHeader('Status', 201);

    if (!empty($wrapper->url) && $url = $wrapper->url->value()) {
      $this->setHttpHeader('Location', $url);
    }

    // The access calls use the request method. Fake the view to be a GET.
    $old_request = $this->getRequest();
    $this->getRequest()->setMethod(RequestInterface::METHOD_GET);
    $output = array($this->view($identifier));
    // Put the original request back to a PUT/PATCH.
    $this->request = $old_request;

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function remove($identifier) {
    $this->isValidEntity('delete', $identifier);

    /* @var \EntityDrupalWrapper $wrapper */
    $wrapper = entity_metadata_wrapper($this->entityType, $identifier);
    $wrapper->delete();

    // Set the HTTP headers.
    $this->setHttpHeader('Status', 204);
  }

  /**
   * {@inheritdoc}
   */
  public function canonicalPath($path) {
    $ids = explode(Resource::IDS_SEPARATOR, $path);
    $canonical_ids = array_map(array($this, 'getEntityIdByFieldId'), $ids);
    return implode(Resource::IDS_SEPARATOR, $canonical_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function entityPreSave(\EntityDrupalWrapper $wrapper) {}

  /**
   * {@inheritdoc}
   */
  public function entityValidate(\EntityDrupalWrapper $wrapper) {
    if (!module_exists('entity_validator')) {
      // Entity validator doesn't exist.
      return;
    }

    try {
      $validator_handler = ValidatorPluginManager::EntityValidator($wrapper->type(), $wrapper->getBundle());
    }
    catch (PluginNotFoundException $e) {
      // Entity validator handler doesn't exist for the entity.
      return;
    }

    if ($validator_handler->validate($wrapper->value(), TRUE)) {
      // Entity is valid.
      return;
    }

    $errors = $validator_handler->getErrors(FALSE);

    $map = array();
    foreach ($this->fieldDefinitions as $resource_field_name => $resource_field) {
      if (!$property = $resource_field->getProperty()) {
        continue;
      }

      $public_name = $resource_field->getPublicName();
      if (empty($errors[$public_name])) {
        // Field validated.
        continue;
      }

      $map[$public_name] = $resource_field_name;
      $params['@fields'][] = $resource_field_name;
    }

    if (empty($params['@fields'])) {
      // There was a validation error, but on non-public fields, so we need to
      // throw an exception, but can't say on which fields it occurred.
      throw new BadRequestException('Invalid value(s) sent with the request.');
    }

    $params['@fields'] = implode(',', $params['@fields']);
    $exception = new BadRequestException(format_plural(count($map), 'Invalid value in field @fields.', 'Invalid values in fields @fields.', $params));
    foreach ($errors as $property_name => $messages) {
      if (empty($map[$property_name])) {
        // Entity is not valid, but on a field not public.
        continue;
      }

      $resource_field_name = $map[$property_name];

      foreach ($messages as $message) {

        $message['params']['@field'] = $resource_field_name;
        $output = format_string($message['message'], $message['params']);

        $exception->addFieldError($resource_field_name, $output);
      }
    }

    // Throw the exception.
    throw $exception;
  }

  /**
   * Get the entity ID based on the ID provided in the request.
   *
   * As any field may be used as the ID, we convert it to the numeric internal
   * ID of the entity
   *
   * @param mixed $id
   *   The provided ID.
   *
   * @throws BadRequestException
   * @throws UnprocessableEntityException
   *
   * @return int
   *   The entity ID.
   */
  protected function getEntityIdByFieldId($id) {
    $request = $this->getRequest();
    $input = $request->getParsedInput();
    $public_property_name = empty($input['loadByFieldName']) ? NULL : $input['loadByFieldName'];
    $public_property_name = $public_property_name ?: (empty($this->options['idField']) ? NULL : $this->options['idField']);
    if (!$public_property_name) {
      // The regular entity ID was provided.
      return $id;
    }
    // We need to get the internal field/property from the public name.
    if ((!$public_field_info = $this->fieldDefinitions->get($public_property_name)) || !$public_field_info->getProperty()) {
      throw new BadRequestException(format_string('Cannot load an entity using the field "@name"', array(
        '@name' => $public_property_name,
      )));
    }
    $query = $this->getEntityFieldQuery();
    $query->range(0, 1);
    // Find out if the provided ID is a Drupal field or an entity property.
    $property = $public_field_info->getProperty();
    /* @var ResourceFieldEntity $public_field_info */
    if (ResourceFieldEntity::propertyIsField($property)) {
      $query->fieldCondition($property, $public_field_info->getColumn(), $id);
    }
    else {
      $query->propertyCondition($property, $id);
    }

    // Execute the query and gather the results.
    $result = $query->execute();
    if (empty($result[$this->entityType])) {
      throw new UnprocessableEntityException(format_string('The entity ID @id by @name cannot be loaded.', array(
        '@id' => $id,
        '@name' => $public_property_name,
      )));
    }

    // There is nothing that guarantees that there is only one result, since
    // this is user input data. Return the first ID.
    $entity_id = key($result[$this->entityType]);

    return $entity_id;
  }

  /**
   * Initialize an EntityFieldQuery (or extending class).
   *
   * @return \EntityFieldQuery
   *   The initialized query with the basics filled in.
   */
  protected function getEntityFieldQuery() {
    $query = $this->EFQObject();
    $entity_type = $this->entityType;
    $query->entityCondition('entity_type', $entity_type);
    $entity_info = $this->getEntityInfo();
    if (!empty($this->bundles) && $entity_info['entity keys']['bundle']) {
      $query->entityCondition('bundle', $this->bundles, 'IN');
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function EFQObject() {
    $efq_class = $this->EFQClass;
    return new $efq_class();
  }

  /**
   * Get the entity info for the current entity the endpoint handling.
   *
   * @param string $type
   *   Optional. The entity type.
   *
   * @return array
   *   The entity info.
   *
   * @see entity_get_info().
   */
  protected function getEntityInfo($type = NULL) {
    return entity_get_info($type ? $type : $this->entityType);
  }

  /**
   * Prepare a query for RestfulEntityBase::getList().
   *
   * @return \Drupal\restful\Util\EntityFieldQuery
   *   The EntityFieldQuery object.
   */
  protected function getQueryForList() {
    $query = $this->getEntityFieldQuery();

    // If we are trying to filter or sort on a computed field, just ignore it
    // and log an exception.
    try {
      $this->queryForListSort($query);
    }
    catch (ServerConfigurationException $e) {
      watchdog_exception('restful', $e);
    }
    try {
      $this->queryForListFilter($query);
    }
    catch (ServerConfigurationException $e) {
      watchdog_exception('restful', $e);
    }

    $this->queryForListPagination($query);
    $this->addExtraInfoToQuery($query);

    return $query;
  }

  /**
   * Prepare a query for RestfulEntityBase::count().
   *
   * @return \EntityFieldQuery
   *   The EntityFieldQuery object.
   */
  protected function getQueryCount() {
    $query = $this->getEntityFieldQuery();

    // If we are trying to filter on a computed field, just ignore it and log an
    // exception.
    try {
      $this->queryForListFilter($query);
    }
    catch (BadRequestException $e) {
      watchdog_exception('restful', $e);
    }

    $this->addExtraInfoToQuery($query);

    return $query->count();
  }

  /**
   * Adds query tags and metadata to the EntityFieldQuery.
   *
   * @param \EntityFieldQuery $query
   *   The query to enhance.
   */
  protected function addExtraInfoToQuery($query) {
    parent::addExtraInfoToQuery($query);
    // The only time you need to add the access tags to a EFQ is when you don't
    // have fieldConditions.
    if (empty($query->fieldConditions) && empty($query->order)) {
      // Add a generic entity access tag to the query.
      $query->addTag($this->entityType . '_access');
    }
    $query->addMetaData('restful_data_provider', $this);
  }

  /**
   * Sort the query for list.
   *
   * @param \EntityFieldQuery $query
   *   The query object.
   *
   * @throws \Drupal\restful\Exception\BadRequestException
   * @throws \EntityFieldQueryException
   *
   * @see \RestfulEntityBase::getQueryForList
   */
  protected function queryForListSort(\EntityFieldQuery $query) {
    $resource_fields = $this->fieldDefinitions;

    // Get the sorting options from the request object.
    $sorts = $this->parseRequestForListSort();

    $sorts = $sorts ? $sorts : $this->defaultSortInfo();

    foreach ($sorts as $public_field_name => $direction) {
      // Determine if sorting is by field or property.
      /* @var \Drupal\restful\Plugin\resource\Field\ResourceFieldEntityInterface $resource_field */
      if (!$resource_field = $resource_fields->get($public_field_name)) {
        return;
      }
      $sort = array(
        'public_field' => $public_field_name,
        'direction' => $direction,
        'resource_id' => $this->pluginId,
      );
      $sort = $this->alterSortQuery($sort, $query);
      if (!empty($sort['processed'])) {
        // If the sort was already processed by the alter filters, continue.
        continue;
      }
      if (!$property_name = $resource_field->getProperty()) {
        if (!$resource_field instanceof ResourceFieldEntityAlterableInterface) {
          throw new BadRequestException('The current sort selection does not map to any entity property or Field API field.');
        }
        // If there was no property but the resource field was sortable, do
        // not add the default field filtering.
        // TODO: This is a workaround. The filtering logic should live in the resource field class.
        return;
      }
      if (ResourceFieldEntity::propertyIsField($property_name)) {
        $query->fieldOrderBy($property_name, $resource_field->getColumn(), $sort['direction']);
      }
      else {
        $column = $this->getColumnFromProperty($property_name);
        $query->propertyOrderBy($column, $sort['direction']);
      }
    }
  }

  /**
   * Filter the query for list.
   *
   * @param \EntityFieldQuery $query
   *   The query object.
   *
   * @throws \Drupal\restful\Exception\BadRequestException
   *
   * @see \RestfulEntityBase::getQueryForList
   */
  protected function queryForListFilter(\EntityFieldQuery $query) {
    $resource_fields = $this->fieldDefinitions;
    $filters = $this->parseRequestForListFilter();
    $this->validateFilters($filters);
    foreach ($filters as $filter) {
      // Determine if filtering is by field or property.
      /* @var \Drupal\restful\Plugin\resource\Field\ResourceFieldEntityInterface $resource_field */
      if (!$resource_field = $resource_fields->get($filter['public_field'])) {
        if (!static::isNestedField($filter['public_field'])) {
          // This is not a nested filter.
          continue;
        }
        if (!empty($filter['target'])) {
          // If we cannot find the field, it may be a nested filter. Check if
          // the target of that is the current resource.
          continue;
        }
        $this->addNestedFilter($filter, $query);
        continue;
      }

      // Give the chance for other data providers to have a special handling for
      // a given field.
      $filter = $this->alterFilterQuery($filter, $query);
      if (!empty($filter['processed'])) {
        // If the filter was already processed by the alter filters, continue.
        continue;
      }
      if (!$property_name = $resource_field->getProperty()) {
        if (!$resource_field instanceof ResourceFieldEntityAlterableInterface) {
          throw new BadRequestException(sprintf('The current filter "%s" selection does not map to any entity property or Field API field and has no custom filtering.', $filter['public_field']));
        }
        // If there was no property but the resource field was filterable, do
        // not add the default field filtering.
        // TODO: This is a workaround. The filtering logic should live in the resource field class.
        return;
      }
      if (field_info_field($property_name)) {
        if ($this::isMultipleValuOperator($filter['operator'][0])) {
          $query->fieldCondition($property_name, $resource_field->getColumn(), $this->getReferencedIds($filter['value'], $resource_field), $filter['operator'][0]);
          continue;
        }
        for ($index = 0; $index < count($filter['value']); $index++) {
          // If referencing an entity by an alternate ID, retrieve the actual
          // Drupal's entity ID using getReferencedId.
          $query->fieldCondition($property_name, $resource_field->getColumn(), $this->getReferencedId($filter['value'][$index], $resource_field), $filter['operator'][$index]);
        }
      }
      else {
        $column = $this->getColumnFromProperty($property_name);
        if ($this::isMultipleValuOperator($filter['operator'][0])) {
          $query->propertyCondition($column, $this->getReferencedIds($filter['value'], $resource_field), $filter['operator'][0]);
          continue;
        }
        for ($index = 0; $index < count($filter['value']); $index++) {
          $query->propertyCondition($column, $this->getReferencedId($filter['value'][$index], $resource_field), $filter['operator'][$index]);
        }
      }
    }
  }

  /**
   * Placeholder method to alter the filters.
   *
   * If no further processing for the filter is needed (i.e. alterFilterQuery
   * already added the query filters to $query), then set the 'processed' flag
   * in $filter to TRUE. Otherwise normal filtering will be added on top,
   * leading to unexpected results.
   *
   * @param array $filter
   *   The parsed filter information.
   * @param \EntityFieldQuery $query
   *   The EFQ to add the filter to.
   *
   * @return array
   *   The modified $filter array.
   */
  protected function alterFilterQuery(array $filter, \EntityFieldQuery $query) {
    if (!$resource_field = $this->fieldDefinitions->get($filter['public_field'])) {
      return $filter;
    }
    if (!$resource_field instanceof ResourceFieldEntityAlterableInterface) {
      // Check if the resource can check on decorated instances.
      if (!$resource_field instanceof ExplorableDecoratorInterface || !$resource_field->isInstanceOf(ResourceFieldEntityAlterableInterface::class)) {
        return $filter;
      }
    }
    return $resource_field->alterFilterEntityFieldQuery($filter, $query);
  }

  /**
   * Placeholder method to alter the filters.
   *
   * If no further processing for the filter is needed (i.e. alterFilterQuery
   * already added the query filters to $query), then set the 'processed' flag
   * in $filter to TRUE. Otherwise normal filtering will be added on top,
   * leading to unexpected results.
   *
   * @param array $sort
   *   The sort array containing the keys:
   *     - public_field: Contains the public property.
   *     - direction: The sorting direction, either ASC or DESC.
   *     - resource_id: The resource machine name.
   * @param \EntityFieldQuery $query
   *   The EFQ to add the filter to.
   *
   * @return array
   *   The modified $sort array.
   */
  protected function alterSortQuery(array $sort, \EntityFieldQuery $query) {
    if (!$resource_field = $this->fieldDefinitions->get($sort['public_field'])) {
      return $sort;
    }
    if (!$resource_field instanceof ResourceFieldEntityAlterableInterface) {
      // Check if the resource can check on decorated instances.
      if (!$resource_field instanceof ExplorableDecoratorInterface || !$resource_field->isInstanceOf(ResourceFieldEntityAlterableInterface::class)) {
        return $sort;
      }
    }
    return $resource_field->alterSortEntityFieldQuery($sort, $query);
  }

  /**
   * Checks if the operator accepts multiple values.
   *
   * @param $operator_name
   *   The name of the operator.
   *
   * @return bool
   *   TRUE if the operator can interpret multiple values. FALSE otherwise.
   */
  protected static function isMultipleValuOperator($operator_name) {
    return in_array(strtoupper($operator_name), array('IN', 'NOT IN', 'BETWEEN'));
  }

  /**
   * Validates the query parameters.
   *
   * @param array $filters
   *   The parsed filters.
   *
   * @throws BadRequestException
   *   When there is an invalid target for relational filters.
   */
  protected function validateFilters(array $filters) {
    foreach ($filters as $filter) {
      if (empty($filter['target'])) {
        continue;
      }
      // If the target is not a part of the field, then raise an error.
      $field_name_parts = explode('.', $filter['public_field']);
      $target_parts = explode('.', $filter['target']);
      foreach ($target_parts as $delta => $target_part) {
        if ($target_part != $field_name_parts[$delta]) {
          // There is a discrepancy between target and field name.
          throw new BadRequestException(sprintf('The target "%s" should be a part of the field name "%s".', $filter['target'], $filter['public_field']));
        }
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
   * @throws BadRequestException
   *
   * @see \RestfulEntityBase::getQueryForList
   */
  protected function queryForListPagination(\EntityFieldQuery $query) {
    list($offset, $range) = $this->parseRequestForListPagination();
    $query->range($offset, $range);
  }

  /**
   * Overrides DataProvider::isValidOperatorsForFilter().
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
        throw new BadRequestException(sprintf('Operator "%s" is not allowed for filtering on this resource. Allowed operators are: %s', $operator, implode(', ', $allowed_operators)));
      }
    }
  }

  /**
   * Overrides DataProvider::isValidConjunctionForFilter().
   */
  protected static function isValidConjunctionForFilter($conjunction) {
    $allowed_conjunctions = array(
      'AND',
    );

    if (!in_array(strtoupper($conjunction), $allowed_conjunctions)) {
      throw new BadRequestException(format_string('Conjunction "@conjunction" is not allowed for filtering on this resource. Allowed conjunctions are: !allowed', array(
        '@conjunction' => $conjunction,
        '!allowed' => implode(', ', $allowed_conjunctions),
      )));
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
    $property_info = entity_get_property_info($this->entityType);
    return $property_info['properties'][$property_name]['schema field'];
  }

  /**
   * Determine if an entity is valid, and accessible.
   *
   * @param string $op
   *   The operation to perform on the entity (view, update, delete).
   * @param int $entity_id
   *   The entity ID.
   *
   * @return bool
   *   TRUE if entity is valid, and user can access it.
   *
   * @throws UnprocessableEntityException
   * @throws InaccessibleRecordException
   */
  protected function isValidEntity($op, $entity_id) {
    $entity_type = $this->entityType;

    if (!ctype_digit((string) $entity_id) || !$entity = entity_load_single($entity_type, $entity_id)) {
      // We need to check if the entity ID is numeric since if this is a uuid
      // that starts by the number 4, and there is an entity with ID 4 that
      // entity will be loaded incorrectly.
      throw new UnprocessableEntityException(sprintf('The entity ID %s does not exist.', $entity_id));
    }

    list(,, $bundle) = entity_extract_ids($entity_type, $entity);

    if (!empty($this->bundles) && !in_array($bundle, $this->bundles)) {
      return FALSE;
    }

    if ($this->checkEntityAccess($op, $entity_type, $entity) === FALSE) {

      if ($op == 'view' && !$this->getResourcePath()) {
        // Just return FALSE, without an exception, for example when a list of
        // entities is requested, and we don't want to fail all the list because
        // of a single item without access.
        // Add the inaccessible item to the metadata to fix the record count in
        // the formatter.
        $inaccessible_records = $this->getMetadata()->get('inaccessible_records');
        $inaccessible_records[] = array(
          'resource' => $this->pluginId,
          'id' => $entity_id,
        );
        $this->getMetadata()->set('inaccessible_records', $inaccessible_records);

        return FALSE;
      }

      // Entity was explicitly requested so we need to throw an exception.
      throw new InaccessibleRecordException(sprintf('You do not have access to entity ID %s.', $entity_id));
    }

    return TRUE;
  }

  /**
   * Check access to CRUD an entity.
   *
   * @param string $op
   *   The operation. Allowed values are "create", "update" and "delete".
   * @param string $entity_type
   *   The entity type.
   * @param object $entity
   *   The entity object.
   *
   * @return bool
   *   TRUE or FALSE based on the access. If no access is known about the entity
   *   return NULL.
   */
  protected function checkEntityAccess($op, $entity_type, $entity) {
    $account = $this->getAccount();
    return entity_access($op, $entity_type, $entity, $account);
  }

  /**
   * Set properties of the entity based on the request, and save the entity.
   *
   * @param \EntityDrupalWrapper $wrapper
   *   The wrapped entity object, passed by reference.
   * @param array $object
   *   The keyed array of properties sent in the payload.
   * @param bool $replace
   *   Determine if properties that are missing from the request array should
   *   be treated as NULL, or should be skipped. Defaults to FALSE, which will
   *   set the fields to NULL.
   *
   * @throws BadRequestException
   *   If the provided object is not valid.
   */
  protected function setPropertyValues(\EntityDrupalWrapper $wrapper, $object, $replace = FALSE) {
    if (!is_array($object)) {
      throw new BadRequestException('Bad input data provided. Please, check your input and your Content-Type header.');
    }
    $save = FALSE;
    $original_object = $object;
    $interpreter = new DataInterpreterEMW($this->getAccount(), $wrapper);
    // Keeps a list of the fields that have been set.
    $processed_fields = array();

    $field_definitions = clone $this->fieldDefinitions;
    foreach ($field_definitions as $public_field_name => $resource_field) {
      /* @var \Drupal\restful\Plugin\resource\Field\ResourceFieldEntityInterface $resource_field */

      if (!$this->methodAccess($resource_field)) {
        // Allow passing the value in the request.
        unset($original_object[$public_field_name]);
        continue;
      }

      $property_name = $resource_field->getProperty();
      if ($resource_field->isComputed()) {
        // We may have for example an entity with no label property, but with a
        // label callback. In that case the $info['property'] won't exist, so
        // we skip this field.
        unset($original_object[$public_field_name]);
        continue;
      }

      $entity_property_access = $this::checkPropertyAccess($resource_field, 'edit', $interpreter);
      if (!array_key_exists($public_field_name, $object)) {
        // No property to set in the request.
        // Only set this to NULL if this property has not been set to a specific
        // value by another public field (since 2 public fields can reference
        // the same property).
        if ($replace && $entity_property_access && !in_array($property_name, $processed_fields)) {
          // We need to set the value to NULL.
          $field_value = NULL;
        }
        else {
          // Either we shouldn't set missing fields as NULL or access is denied
          // for the current property, hence we skip.
          continue;
        }
      }
      else {
        // Property is set in the request.
        // Delegate modifications on the value of the field.
        $field_value = $resource_field->preprocess($object[$public_field_name]);
      }
      $resource_field->set($field_value, $interpreter);
      // We check the property access only after setting the values, as the
      // access callback's response might change according to the field value.
      $entity_property_access = $this::checkPropertyAccess($resource_field, 'edit', $interpreter);
      if (!$entity_property_access) {
        throw new BadRequestException(format_string('Property @name cannot be set.', array('@name' => $public_field_name)));
      }

      $processed_fields[] = $property_name;
      unset($original_object[$public_field_name]);
      $save = TRUE;
    }

    if (!$save) {
      // No request was sent.
      throw new BadRequestException('No values were sent with the request');
    }

    if ($original_object) {
      // Request had illegal values.
      $error_message = format_plural(count($original_object), 'Property @names is invalid.', 'Properties @names are invalid.', array('@names' => implode(', ', array_keys($original_object))));
      throw new BadRequestException($error_message);
    }

    // Allow changing the entity just before it's saved. For example, setting
    // the author of the node entity.
    $this->entityPreSave($interpreter->getWrapper());

    $this->entityValidate($interpreter->getWrapper());

    $wrapper->save();
  }

  /**
   * Validates the body payload object for entities.
   *
   * @param mixed $body
   *   The parsed body.
   *
   * @throws \Drupal\restful\Exception\BadRequestException
   *   For the empty body.
   */
  protected function validateBody($body) {
    if (isset($body) && !is_array($body)) {
      $message = sprintf('Incorrect object parsed: %s', print_r($body, TRUE));
      throw new BadRequestException($message);
    }
  }

  /**
   * Checks if the data provider user has access to the property.
   *
   * @param \Drupal\restful\Plugin\resource\Field\ResourceFieldInterface $resource_field
   *   The field to check access on.
   * @param string $op
   *   The operation to be performed on the field.
   * @param \Drupal\restful\Plugin\resource\DataInterpreter\DataInterpreterInterface $interpreter
   *   The data interpreter.
   *
   * @return bool
   *   TRUE if the user has access to the property.
   */
  protected static function checkPropertyAccess(ResourceFieldInterface $resource_field, $op, DataInterpreterInterface $interpreter) {
    return $resource_field->access($op, $interpreter);
  }

  /**
   * Get referenced ID.
   *
   * @param string $value
   *   The provided value, it can be an ID or not.
   * @param ResourceFieldInterface $resource_field
   *   The resource field that points to another entity.
   *
   * @return string
   *   If the field uses an alternate ID property, then the ID gets translated
   *   to the original entity ID. If not, then the same provided ID is returned.
   *
   * @todo: Add testing to this functionality.
   */
  protected function getReferencedId($value, ResourceFieldInterface $resource_field) {
    $field_definition = $resource_field->getDefinition();
    if (empty($field_definition['referencedIdProperty'])) {
      return $value;
    }
    // Get information about the the Drupal field to see what entity type we are
    // dealing with.
    $field_info = field_info_field($resource_field->getProperty());
    // We support:
    // - Entity Reference.
    // - Taxonomy Term.
    // - File & Image field.
    // - uid property.
    // - vid property.
    // If you need to support other types, you can create a custom data provider
    // that overrides this method.
    $target_entity_type = NULL;
    $bundles = array();
    if (!$field_info) {
      if ($resource_field->getProperty() == 'uid') {
        // We make a special case for the user id.
        $target_entity_type = 'user';
      }
      elseif ($resource_field->getProperty() == 'vid') {
        // We make a special case for the vocabulary id.
        $target_entity_type = 'taxonomy_vocabulary';
      }
    }
    elseif (!empty($field_info['type']) && $field_info['type'] == 'entityreference') {
      $target_entity_type = $field_info['settings']['target_type'];
      $bundles = empty($field_info['settings']['handler_settings']['target_bundles']) ? array() : $field_info['settings']['handler_settings']['target_bundles'];
    }
    elseif (!empty($field_info['type']) && $field_info['type'] == 'file') {
      $target_entity_type = 'file';
    }
    elseif (!empty($field_info['type']) && $field_info['type'] == 'taxonomy_term_reference') {
      $target_entity_type = 'taxonomy_term';
      // Narrow down with the vocabulary information. Very useful if there are
      // multiple terms with the same name in different vocabularies.
      foreach ($field_info['settings']['allowed_values'] as $allowed_value) {
        $bundles[] = $allowed_value['vocabulary'];
      }
    }
    if (empty($target_entity_type) && $resource_field instanceof ResourceFieldResourceInterface && ($resource_info = $resource_field->getResource())) {
      $instance_id = sprintf('%s:%d.%d', $resource_info['name'], $resource_info['majorVersion'], $resource_info['minorVersion']);
      try {
        $handler = restful()->getResourceManager()->getPlugin($instance_id);
        if ($handler instanceof ResourceEntity) {
          $target_entity_type = $handler->getEntityType();
          $bundles = $handler->getBundles();
        }
      }
      catch (PluginNotFoundException $e) {
        // Do nothing.
      }
    }
    if (empty($target_entity_type)) {
      return $value;
    }

    // Now we have the entity type and bundles to look for the entity based on
    // the contents of the field or the entity property.
    $query = $this->EFQObject();
    $query->entityCondition('entity_type', $target_entity_type);
    if (!empty($bundles)) {
      // Narrow down for bundles.
      $query->entityCondition('bundle', $bundles, 'IN');
    }

    // Check if the referencedIdProperty is a field or a property.
    $id_property = $field_definition['referencedIdProperty'];
    if (field_info_field($id_property)) {
      $query->fieldCondition($id_property, $resource_field->getColumn(), $value);
    }
    else {
      $query->propertyCondition($id_property, $value);
    }
    // Only one result is returned. This assumes the reference fields are unique
    // for every entity.
    $results = $query->range(0, 1)->execute();
    if ($results[$target_entity_type]) {
      return key($results[$target_entity_type]);
    }
    // If no entity could be found, fall back to the original value.
    return $value;
  }

  /**
   * Get reference IDs for multiple values.
   *
   * @param array $values
   *   The provided values, they can be an IDs or not.
   * @param ResourceFieldInterface $resource_field
   *   The resource field that points to another entity.
   *
   * @return string
   *   If the field uses an alternate ID property, then the ID gets translated
   *   to the original entity ID. If not, then the same provided ID is returned.
   *
   * @see getReferencedId()
   */
  protected function getReferencedIds(array $values, ResourceFieldInterface $resource_field) {
    $output = array();
    foreach ($values as $value) {
      $output[] = $this->getReferencedId($value, $resource_field);
    }
    return $output;
  }

  /**
   * Add relational filters to EFQ.
   *
   * This is for situation like when you only want articles that have taxonomies
   * that contain the word Drupal in their body field. This cannot be resolved
   * via EFQ alone.
   *
   * @param array $filter
   *   The filter.
   * @param \EntityFieldQuery $query
   *   The query to alter.
   */
  protected function addNestedFilter(array $filter, \EntityFieldQuery $query) {
    $relational_filters = array();
    foreach ($this->getFieldsInfoFromPublicName($filter['public_field']) as $field_info) {
      $relational_filters[] = new RelationalFilter($field_info['name'], $field_info['type'], $field_info['column'], $field_info['entity_type'], $field_info['bundles'], $field_info['target_column']);
    }
    $query->addRelationship($filter + array('relational_filters' => $relational_filters));
  }

  /**
   * Transform the nested public name into an array of Drupal field information.
   *
   * @param string $name
   *   The dot separated public name.
   *
   * @throws ServerConfigurationException
   *   When the required resource information is not available.
   * @throws BadRequestException
   *   When the nested field is invalid.
   *
   * @return array
   *   An array of fields with name and type.
   */
  protected function getFieldsInfoFromPublicName($name) {
    $public_field_names = explode('.', $name);
    $last_public_field_name = array_pop($public_field_names);
    $fields = array();

    // The first field is in the current resource, but not the other ones.
    $definitions = $this->fieldDefinitions;
    foreach ($public_field_names as $index => $public_field_name) {
      /* @var ResourceFieldEntity $resource_field */
      $resource_field = $definitions->get($public_field_name);
      // Get the resource for the field, so we can get information for the next
      // iteration.
      if (!$resource_field || !($resource = $resource_field->getResource())) {
        throw new ServerConfigurationException(sprintf('The nested field %s cannot be accessed because %s has no resource associated to it.', $name, $public_field_name));
      }
      list($item, $definitions) = $this->getFieldsFromPublicNameItem($resource_field);
      $fields[] = $item;
    }
    if (!$resource_field = $definitions->get($last_public_field_name)) {
      throw new BadRequestException(sprintf('Invalid nested field provided %s', $last_public_field_name));
    }
    $property = $resource_field->getProperty();
    $item = array(
      'name' => $property,
      'type' => ResourceFieldEntity::propertyIsField($property) ? RelationalFilterInterface::TYPE_FIELD : RelationalFilterInterface::TYPE_PROPERTY,
      'entity_type' => NULL,
      'bundles' => array(),
      'target_column' => NULL,
    );
    $item['column'] = $item['type'] == RelationalFilterInterface::TYPE_FIELD ? $resource_field->getColumn() : NULL;
    $fields[] = $item;

    return $fields;
  }

  /**
   * Get the (reference) field information for a single item.
   *
   * @param ResourceFieldInterface $resource_field
   *   The resource field.
   *
   * @throws \Drupal\restful\Exception\BadRequestException
   *
   * @return array
   *   An array containing the following keys:
   *   - 'name': Drupal's internal field name. Ex: field_article_related
   *   - 'type': Either a field or a property.
   *   - 'entity_type': The entity type this field points to. Not populated if
   *     the field is not a reference (for instance the destination field used
   *     in the where clause).
   *   - 'bundles': The allowed bundles for this field. Not populated if the
   *     field is not a reference (for instance the destination field used in
   *     the where clause).
   */
  protected function getFieldsFromPublicNameItem(ResourceFieldResourceInterface $resource_field) {
    $property = $resource_field->getProperty();
    $item = array(
      'name' => $property,
      'type' => ResourceFieldEntity::propertyIsField($property) ? RelationalFilterInterface::TYPE_FIELD : RelationalFilterInterface::TYPE_PROPERTY,
      'entity_type' => NULL,
      'bundles' => array(),
      'target_column' => $resource_field->getTargetColumn(),
    );
    $item['column'] = $item['type'] == RelationalFilterInterface::TYPE_FIELD ? $resource_field->getColumn() : NULL;
    /* @var ResourceEntity $resource */
    $resource = $resource_field->getResourcePlugin();

    // Variables for the next iteration.
    $definitions = $resource->getFieldDefinitions();
    $item['entity_type'] = $resource->getEntityType();
    $item['bundles'] = $resource->getBundles();
    return array($item, $definitions);
  }

  /**
   * {@inheritdoc}
   */
  protected function initDataInterpreter($identifier) {
    $id = $identifier;
    $entity_id = $this->getEntityIdByFieldId($id);
    /* @var \EntityDrupalWrapper $wrapper */
    $wrapper = entity_metadata_wrapper($this->entityType, $entity_id);
    $wrapper->language($this->getLangCode());
    return new DataInterpreterEMW($this->getAccount(), $wrapper);
  }

}

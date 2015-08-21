<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\DataProvider\DataProviderEntity.
 */

namespace Drupal\restful\Plugin\resource\DataProvider;

use Drupal\restful\Exception\ForbiddenException;
use Drupal\restful\Exception\InternalServerErrorException;
use Drupal\restful\Exception\ServerConfigurationException;
use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\DataInterpreter\DataInterpreterEMW;
use Drupal\restful\Plugin\resource\DataInterpreter\DataInterpreterInterface;
use Drupal\restful\Plugin\resource\Field\ResourceFieldCollection;
use Drupal\restful\Plugin\resource\Field\ResourceFieldCollectionInterface;
use Drupal\restful\Plugin\resource\Field\ResourceFieldEntity;
use Drupal\restful\Plugin\resource\Field\ResourceFieldEntityInterface;
use Drupal\restful\Exception\BadRequestException;
use Drupal\restful\Exception\UnprocessableEntityException;
use Drupal\restful\Plugin\resource\Field\ResourceFieldInterface;
use Drupal\restful\Plugin\resource\Resource;
use Drupal\restful\Plugin\resource\ResourceInterface;

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
  protected $EFQClass = '\EntityFieldQuery';

  /**
   * Constructor.
   *
   * @param RequestInterface $request
   *   The request.
   * @param ResourceFieldCollectionInterface $field_definitions
   *   The field definitions.
   * @param object $account
   *   The account object.
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
  public function __construct(RequestInterface $request, ResourceFieldCollectionInterface $field_definitions, $account, $resource_path, array $options, $langcode = NULL) {
    parent::__construct($request, $field_definitions, $account, $resource_path, $options, $langcode);
    if (empty($options['entityType'])) {
      // Entity type is mandatory.
      throw new InternalServerErrorException('The entity type was not provided.');
    }
    $this->entityType = $options['entityType'];
    if (isset($options['bundles'])) {
      $this->bundles = $options['bundles'];
    }
    if (isset($options['EFQClass'])) {
      $this->EFQClass = $options['EFQClass'];
    }

    foreach ($this->fieldDefinitions as $key => $value) {
      // Set the entity type and bundles on the resource fields.
      if (!($value instanceof ResourceFieldEntityInterface)) {
        continue;
      }
      /* @var ResourceFieldEntityInterface $value */
      $value->setEntityType($this->entityType);
      if (!$value->getBundles()) {
        // If the field definition does not contain an array of bundles for that
        // field then assume that the field applies to all the bundles of the
        // resource.
        if (!$this->bundles && $entity_type = $this->entityType) {
          // If no bundles are passed, then assume all the bundles of the entity
          // type.
          $entity_info = entity_get_info($entity_type);
          $this->bundles = array_keys($entity_info['bundles']);
        }

        $value->setBundles($this->bundles);
      }
    }
    $this->resourcePath = $resource_path;
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
  public function getContext($identifier) {
    if (is_array($identifier)) {
      // Like in https://example.org/api/articles/1,2,3.
      $identifier = implode(ResourceInterface::IDS_SEPARATOR, $identifier);
    }
    return array(
      'et' => $this->entityType,
      'ei' => $identifier,
    );
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

    return intval($query->count()->execute());
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
    $id = $identifier;
    $entity_id = $this->getEntityIdByFieldId($id);

    if (!$this->isValidEntity('view', $entity_id)) {
      return NULL;
    }

    $resource_field_collection = new ResourceFieldCollection();
    /* @var \EntityDrupalWrapper $wrapper */
    $wrapper = entity_metadata_wrapper($this->entityType, $entity_id);
    $wrapper->language($this->getLangCode());
    $interpreter = new DataInterpreterEMW($this->getAccount(), $wrapper);
    $resource_field_collection->setInterpreter($interpreter);
    $id_field_name = empty($this->options['idField']) ? 'id' : $this->options['idField'];
    $resource_field_collection->setIdField($this->fieldDefinitions->get($id_field_name));

    $input = $this->getRequest()->getParsedInput();
    $limit_fields = !empty($input['fields']) ? explode(',', $input['fields']) : array();

    foreach ($this->fieldDefinitions as $resource_field_name => $resource_field) {
      // Create an empty field collection and populate it with the appropriate
      // resource fields.
      /* @var ResourceFieldEntityInterface $resource_field */

      if ($limit_fields && !in_array($resource_field_name, $limit_fields)) {
        // Limit fields doesn't include this property.
        continue;
      }

      if (!$this->methodAccess($resource_field) || !$resource_field->access('view', $interpreter)) {
        // The field does not apply to the current method or has denied access.
        continue;
      }

      $resource_field_collection->set($resource_field->id(), $resource_field);
    }

    return $resource_field_collection;
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $identifiers) {
    $return = array();
    // If no IDs were requested, we should not throw an exception in case an
    // entity is un-accessible by the user.
    foreach ($identifiers as $identifier) {
      if ($row = $this->view($identifier)) {
        $return[] = $row;
      }
    }

    return $return;
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

    return array($this->view($wrapper->getIdentifier()));
  }

  /**
   * {@inheritdoc}
   */
  public function remove($identifier) {
    $this->isValidEntity('update', $identifier);

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

    if (!$handler = entity_validator_get_validator_handler($wrapper->type(), $wrapper->getBundle())) {
      // Entity validator handler doesn't exist for the entity.
      return;
    }

    if ($handler->validate($wrapper->value(), TRUE)) {
      // Entity is valid.
      return;
    }

    $errors = $handler->getErrors(FALSE);

    $map = array();
    foreach ($this->fieldDefinitions as $resource_field_name => $resource_field) {
      /* @var ResourceFieldInterface */
      if ($property = $resource_field->getProperty()) {
        continue;
      }

      if (empty($errors[$property])) {
        // Field validated.
        continue;
      }

      $map[$property] = $resource_field_name;
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
   * @return \EntityFieldQuery
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
   * Adds query tags and metadata to the EntityFieldQuery.
   *
   * @param \EntityFieldQuery $query
   *   The query to enhance.
   */
  protected function addExtraInfoToQuery($query) {
    parent::addExtraInfoToQuery($query);
    // The only time you need to add the access tags to a EFQ is when you don't
    // have fieldConditions.
    if (empty($query->fieldConditions)) {
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
      /* @var ResourceFieldEntityInterface $resource_field */
      if (!$resource_field = $resource_fields->get($public_field_name)) {
        return;
      }
      if (!$property_name = $resource_field->getProperty()) {
        throw new BadRequestException('The current sort selection does not map to any entity property or Field API field.');
      }
      if (ResourceFieldEntity::propertyIsField($property_name)) {
        $query->fieldOrderBy($property_name, $resource_field->getColumn(), $direction);
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
   * @throws \Drupal\restful\Exception\BadRequestException
   *
   * @see \RestfulEntityBase::getQueryForList
   */
  protected function queryForListFilter(\EntityFieldQuery $query) {
    $resource_fields = $this->fieldDefinitions;
    foreach ($this->parseRequestForListFilter() as $filter) {
      // Determine if filtering is by field or property.
      /* @var ResourceFieldEntityInterface $resource_field */
      if (!$resource_field = $resource_fields->get($filter['public_field'])) {
        return;
      }
      if (!$property_name = $resource_field->getProperty()) {
        throw new BadRequestException(sprintf('The current filter "%s" selection does not map to any entity property or Field API field.', $filter['public_field']));
      }
      if (field_info_field($property_name)) {
        if (in_array(strtoupper($filter['operator'][0]), array('IN', 'BETWEEN'))) {
          $query->fieldCondition($property_name, $resource_field->getColumn(), $filter['value'], $filter['operator'][0]);
          continue;
        }
        for ($index = 0; $index < count($filter['value']); $index++) {
          $query->fieldCondition($property_name, $resource_field->getColumn(), $filter['value'][$index], $filter['operator'][$index]);
        }
      }
      else {
        $column = $this->getColumnFromProperty($property_name);
        for ($index = 0; $index < count($filter['value']); $index++) {
          $query->propertyCondition($column, $filter['value'][$index], $filter['operator'][$index]);
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
   * @throws ForbiddenException
   */
  protected function isValidEntity($op, $entity_id) {
    $entity_type = $this->entityType;

    if (!$entity = entity_load_single($entity_type, $entity_id)) {
      throw new UnprocessableEntityException(sprintf('The entity ID %s does not exist.', $entity_id));
    }

    list(,, $bundle) = entity_extract_ids($entity_type, $entity);

    if (!empty($this->bundles) && !in_array($bundle, $this->bundles)) {
      throw new UnprocessableEntityException(sprintf('The entity ID %s is not valid.', $entity_id));
    }

    if ($this->checkEntityAccess($op, $entity_type, $entity) === FALSE) {

      if ($op == 'view' && !$this->request->getPath()) {
        // Just return FALSE, without an exception, for example when a list of
        // entities is requested, and we don't want to fail all the list because
        // of a single item without access.
        return FALSE;
      }

      // Entity was explicitly requested so we need to throw an exception.
      throw new ForbiddenException(sprintf('You do not have access to entity ID %s.', $entity_id));
    }

    return TRUE;
  }

  /**
   * Check access to CRUD an entity.
   *
   * @param $op
   *   The operation. Allowed values are "create", "update" and "delete".
   * @param $entity_type
   *   The entity type.
   * @param $entity
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
   *   Determine if properties that are missing form the request array should
   *   be treated as NULL, or should be skipped. Defaults to FALSE, which will
   *   set the fields to NULL.
   *
   * @throws BadRequestException
   */
  protected function setPropertyValues(\EntityDrupalWrapper $wrapper, $object, $replace = FALSE) {
    if (!is_array($object)) {
      throw new BadRequestException('Bad input data provided. Please, check your input and your Content-Type header.');
    }
    $save = FALSE;
    // We cannot set the 'id' property of the $object, it's only needed to know
    // which entity to update. Remove it from the properties to set.
    unset($object['id']);
    $original_object = $object;
    $interpreter = new DataInterpreterEMW($this->getAccount(), $wrapper);
    // Keeps a list of the fields that have been set.
    $processed_fields = array();

    foreach ($this->fieldDefinitions as $public_field_name => $resource_field) {
      /* @var ResourceFieldEntityInterface $resource_field */

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
      if (!isset($object[$public_field_name])) {
        // No property to set in the request.
        // Only set this to NULL if this property has not been set to a specific
        // value by another public field (since 2 public fields can reference
        // the same property).
        if ($replace && $entity_property_access && !in_array($property_name, $processed_fields)) {
          // We need to set the value to NULL.
          $resource_field->set(NULL, $interpreter);
        }
        continue;
      }
      if (!$entity_property_access) {
        throw new BadRequestException(format_string('Property @name cannot be set.', array('@name' => $public_field_name)));
      }

      // Delegate modifications on the value of the field.
      $field_value = $resource_field->preprocess($object[$public_field_name]);

      $resource_field->set($field_value, $interpreter);
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
}

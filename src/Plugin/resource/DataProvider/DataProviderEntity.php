<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\DataProvider\DataProviderEntity.
 */

namespace Drupal\restful\Plugin\resource\DataProvider;

use Drupal\restful\Exception\ForbiddenException;
use Drupal\restful\Exception\InternalServerErrorException;
use Drupal\restful\Exception\ServerConfigurationException;
use Drupal\restful\Http\HttpHeader;
use Drupal\restful\Http\Request;
use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\Field\ResourceFieldBase;
use Drupal\restful\Plugin\resource\Field\ResourceFieldCollectionInterface;
use Drupal\restful\Plugin\resource\Field\ResourceFieldEntity;
use Drupal\restful\Plugin\resource\Field\ResourceFieldEntityInterface;
use Drupal\restful\Exception\BadRequestException;
use Drupal\restful\Exception\UnprocessableEntityException;
use Drupal\restful\Plugin\resource\Resource;
use Drupal\restful\Resource\ResourceManager;

class DataProviderEntity extends DataProvider implements DataProviderEntityInterface{

  // TODO: The Data Provider should be in charge of the entity_access checks.

  /**
   * Field definitions.
   *
   * A collection of
   * \Drupal\restful\Plugin\resource\Field\ResourceFieldEntityInterface[].
   *
   * @var ResourceFieldCollectionInterface
   */
  protected $fieldDefinitions;

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
  public function __construct(RequestInterface $request, ResourceFieldCollectionInterface $field_definitions, $account, array $options, $langcode = NULL) {
    parent::__construct($request, $field_definitions, $account, $options, $langcode);
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

    // Make sure that all field definitions are instance of
    // \Drupal\restful\Plugin\resource\Field\ResourceFieldEntityInterface
    foreach ($this->fieldDefinitions as $key => $value) {
      // Set the entity type and bundles on the resource fields.
      if (!($value instanceof ResourceFieldEntityInterface)) {
        continue;
      }
      /** @var ResourceFieldEntityInterface $value */
      $value->setEntityType($this->entityType);
      if (!$value->getBundles()) {
        // If the field definition does not contain an array of bundles for that
        // field then assume that the field applies to all the bundles of the
        // resource.
        $value->setBundles($this->bundles);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getContext($identifier) {
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
    return array('id' => 'ASC');
  }

  /**
   * {@inheritdoc}
   */
  public function index() {
    $result = $this
      ->getQueryForList()
      ->execute();

    if (empty($result[$this->entityType])) {
      return array();
    }

    $ids = array_keys($result[$this->entityType]);

    return $this->viewMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function create($object) {
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

    /** @var \EntityDrupalWrapper $wrapper */
    $wrapper = entity_metadata_wrapper($this->entityType, $entity);

    $this->setPropertyValues($wrapper, $object, TRUE);
    return array($this->view($wrapper->getIdentifier()));
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

    /** @var \EntityDrupalWrapper $wrapper */
    $wrapper = entity_metadata_wrapper($this->entityType, $entity_id);
    $wrapper->language($this->getLangCode());
    $values = array();

    $limit_fields = !empty($request['fields']) ? explode(',', $request['fields']) : array();

    foreach ($this->fieldDefinitions as $resource_field_name => $resource_field) {
      /** @var ResourceFieldEntityInterface $resource_field */
      if ($limit_fields && !in_array($resource_field_name, $limit_fields)) {
        // Limit fields doesn't include this property.
        continue;
      }

      $value = NULL;

      if (!$this->fieldAccess($resource_field)) {
        // The field does not apply to the current method.
        continue;
      }

      if ($resource_field->getCallback()) {
        // TODO: Use strategy pattern to pass a consistent object to
        // executeCallback.
        $value = ResourceManager::executeCallback($resource_field->getCallback(), array($wrapper));
      }
      elseif ($resource = $resource_field->getResource()) {
        // TODO: The resource input data in the field definition has changed.
        // Now it does not need to be keyed by bundle since you don't even need
        // an entity to use the resource based field.

        $request = Request::create('', array(), Request::METHOD_GET);
        $resource_data_provider = DataProviderResource::init($request, $resource['name'], array(
          $resource['majorVersion'],
          $resource['minorVersion'],
        ));
        $value = $resource_data_provider->view($identifier);
      }
      else {
        $value = $this->getValue($wrapper, $resource_field);
      }

      $value = $this->processCallbacks($value, $resource_field);

      $values[$resource_field_name] = $value;
    }

    return $values;
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
    $entity_id = $this->getEntityIdByFieldId($identifier);
    $this->isValidEntity('update', $entity_id);

    /** @var \EntityDrupalWrapper $wrapper */
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

    /** @var \EntityDrupalWrapper $wrapper */
    $wrapper = entity_metadata_wrapper($this->entityType, $identifier);
    $wrapper->delete();

    // Set the HTTP headers.
    // Set the HTTP headers.
    $this->setHttpHeader('Status', 204);
  }

  /**
   * {@inheritdoc}
   */
  public function canonicalPath($path) {
    $ids = Resource::IDS_SEPARATOR ? explode(Resource::IDS_SEPARATOR, $path) : array($path);
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
      $property = $resource_field['property'];
      if (empty($property)) {
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
   * Sets an HTTP header.
   *
   * @param string $name
   *   The header name.
   * @param string $value
   *   The header value.
   */
  protected function setHttpHeader($name, $value) {
    $this
      ->getRequest()
      ->getHeaders()
      ->add(HttpHeader::create($name, $value));
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
    if (empty($input['loadByFieldName'])) {
      // The regular entity ID was provided.
      return $id;
    }
    $public_property_name = $input['loadByFieldName'];
    // We need to get the internal field/property from the public name.
    if ((!$public_field_info = $this->fieldDefinitions[$public_property_name]) || $public_field_info->getProperty()) {
      throw new BadRequestException(format_string('Cannot load an entity using the field "@name"', array(
        '@name' => $public_property_name,
      )));
    }
    $query = $this->getEntityFieldQuery();
    $query->range(0, 1);
    // Find out if the provided ID is a Drupal field or an entity property.
    if (ResourceFieldEntity::propertyIsField($public_field_info['property'])) {
      $query->fieldCondition($public_field_info['property'], $public_field_info['column'], $id);
    }
    else {
      $query->propertyCondition($public_field_info['property'], $id);
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
   * Gets a EFQ object.
   *
   * @return \EntityFieldQuery
   *   The object that inherits from \EntityFieldQuery.
   */
  protected function EFQObject() {
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
    if ($path = $this->request->getPath()) {
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
   * @throws BadRequestException
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
      /** @var ResourceFieldEntityInterface $resource_field */
      $resource_field = $resource_fields[$public_field_name];
      if (!$property_name = $resource_field->getProperty()) {
        throw new BadRequestException('The current sort selection does not map to any entity property or Field API field.');
      }
      if (ResourceFieldEntityInterface::propertyIsField($property_name)) {
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
   * @throws BadRequestException
   *
   * @see \RestfulEntityBase::getQueryForList
   */
  protected function queryForListFilter(\EntityFieldQuery $query) {
    $resource_fields = $this->fieldDefinitions;
    foreach ($this->parseRequestForListFilter() as $filter) {
      // Determine if filtering is by field or property.
      /** @var ResourceFieldEntityInterface $resource_field */
      $resource_field = $resource_fields[$filter['public_field']];

      if (!$property_name = $resource_field->getProperty()) {
        throw new BadRequestException('The current filter selection does not map to any entity property or Field API field.');
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
   * Check access on a property.
   *
   * @param string $op
   *   The operation that access should be checked for. Can be "view" or "edit".
   *   Defaults to "edit".
   * @param string $public_field_name
   *   The name of the public field.
   * @param \EntityStructureWrapper $property_wrapper
   *   The wrapped property.
   * @param \EntityMetadataWrapper $wrapper
   *   The wrapped entity.
   *
   * @return bool
   *   TRUE if the current user has access to set the property, FALSE otherwise.
   */
  protected function checkPropertyAccess($op, $public_field_name, \EntityStructureWrapper $property_wrapper, \EntityMetadataWrapper $wrapper) {
    if (!$this->checkPropertyAccessByAccessCallbacks($op, $public_field_name, $property_wrapper, $wrapper)) {
      // Access callbacks denied access.
      return FALSE;
    }

    $account = $this->getAccount();
    // Check format access for text fields.
    if ($property_wrapper->type() == 'text_formatted' && $property_wrapper->value() && $property_wrapper->format->value()) {
      $format = (object) array('format' => $property_wrapper->format->value());
      // Only check filter access on write contexts.
      if (Request::isWriteMethod($this->request->getMethod()) && !filter_access($format, $account)) {
        return FALSE;
      }
    }

    $info = $property_wrapper->info();
    if ($op == 'edit' && empty($info['setter callback'])) {
      // Property does not allow setting.
      return FALSE;
    }

    $access = $property_wrapper->access($op, $account);
    return $access !== FALSE;
  }

  /**
   * Check access on property by the defined access callbacks.
   *
   * @param string $op
   *   The operation that access should be checked for. Can be "view" or "edit".
   *   Defaults to "edit".
   * @param string $public_field_name
   *   The name of the public field.
   * @param \EntityMetadataWrapper $property_wrapper
   *   The wrapped property.
   * @param \EntityMetadataWrapper $wrapper
   *   The wrapped entity.
   *
   * @return bool
   *   TRUE if the current user has access to set the property, FALSE otherwise.
   *   The default implementation assumes that if no callback has explicitly
   *   denied access, we grant the user permission.
   */
  protected function checkPropertyAccessByAccessCallbacks($op, $public_field_name, \EntityMetadataWrapper $property_wrapper, \EntityMetadataWrapper $wrapper) {
    $public_fields = $this->fieldDefinitions;

    /** @var ResourceFieldEntityInterface $public_field */
    $public_field = $public_fields[$public_field_name];
    foreach ($public_field->getAccessCallbacks() as $callback) {
      $result = ResourceManager::executeCallback($callback, array(
        $op,
        $public_field_name,
        $property_wrapper,
        $wrapper,
      ));

      if ($result == ResourceFieldBase::ACCESS_DENY) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Gets the value for the provided resource field.
   *
   * @param \EntityDrupalWrapper $wrapper
   *   The wrapped entity.
   * @param ResourceFieldEntityInterface $resource_field
   *   The resource field.
   *
   * @return mixed
   *   The value to return.
   */
  protected function getValue(\EntityDrupalWrapper $wrapper, ResourceFieldEntityInterface $resource_field) {
    // Exposing an entity field.
    $value = NULL;
    $resource_field_name = $resource_field->getPublicName();
    $property = $resource_field->getProperty();
    $sub_wrapper = $resource_field->isWrapperMethodOnEntity() ? $wrapper : $wrapper->{$property};

    // Check user has access to the property.
    if ($property && !$this->checkPropertyAccess('view', $resource_field_name, $sub_wrapper, $wrapper)) {
      return NULL;
    }

    $formatter = $resource_field->getFormatter();
    if (empty($formatter)) {
      if ($sub_wrapper instanceof \EntityListWrapper) {
        // Multiple values.
        foreach ($sub_wrapper as $item_wrapper) {
          $value[] = $this->getValueFromField($wrapper, $item_wrapper, $resource_field);
        }
      }
      else {
        // Single value.
        $value = $this->getValueFromField($wrapper, $sub_wrapper, $resource_field);
      }
    }
    else {
      // Get value from field formatter.
      $value = $this->getValueFromFieldFormatter($wrapper, $sub_wrapper, $resource_field);
    }

    return $value;
  }

  /**
   * Get value from a property.
   *
   * @param \EntityDrupalWrapper $wrapper
   *   The wrapped entity.
   * @param \EntityMetadataWrapper $sub_wrapper
   *   The wrapped property.
   * @param ResourceFieldEntityInterface $resource_field
   *   The public field info array.
   *
   * @return mixed
   *   A single or multiple values.
   */
  protected function getValueFromField(\EntityDrupalWrapper $wrapper, \EntityMetadataWrapper $sub_wrapper, ResourceFieldEntityInterface $resource_field) {
    if ($resource_field->getSubProperty() && $sub_wrapper->value()) {
      $sub_wrapper = $sub_wrapper->{$resource_field->getSubProperty()};
    }

    // Wrapper method.
    $value = $sub_wrapper->{$resource_field->getWrapperMethod()}();

    return $value;
  }

  /**
   * Get value from a field rendered by Drupal field API's formatter.
   *
   * @param \EntityDrupalWrapper $wrapper
   *   The wrapped entity.
   * @param \EntityMetadataWrapper $sub_wrapper
   *   The wrapped property.
   * @param ResourceFieldEntityInterface $resource_field
   *   The resource field.
   *
   * @return mixed
   *   A single or multiple values.
   *
   * @throws \Drupal\restful\Exception\ServerConfigurationException
   */
  protected function getValueFromFieldFormatter(\EntityDrupalWrapper $wrapper, \EntityMetadataWrapper $sub_wrapper, ResourceFieldEntityInterface $resource_field) {
    $value = NULL;

    if (!ResourceFieldEntity::propertyIsField($resource_field->getProperty())) {
      // Property is not a field.
      throw new ServerConfigurationException(format_string('@property is not a configurable field, so it cannot be processed using field API formatter', array('@property' => $resource_field->getProperty())));
    }

    // Get values from the formatter.
    $output = field_view_field($this->entityType, $wrapper->value(), $resource_field->getProperty(), $resource_field->getFormatter());

    // Unset the theme, as we just want to get the value from the formatter,
    // without the wrapping HTML.
    unset($output['#theme']);


    if ($sub_wrapper instanceof \EntityListWrapper) {
      // Multiple values.
      foreach (element_children($output) as $delta) {
        $value[] = drupal_render($output[$delta]);
      }
    }
    else {
      // Single value.
      $value = drupal_render($output);
    }

    return $value;
  }

  /**
   * Applies the process callbacks.
   *
   * @param mixed $value
   *   The value for the field.
   * @param ResourceFieldEntityInterface $resource_field
   *   The resource field.
   *
   * @return mixed
   *   The value after applying all the process callbacks.
   *
   * @throws \Drupal\restful\Exception\ServerConfigurationException
   */
  protected function processCallbacks($value, ResourceFieldEntityInterface $resource_field) {
    $process_callbacks = $resource_field->getProcessCallbacks();
    if (!$value || empty($process_callbacks)) {
      return $value;
    }
    foreach ($process_callbacks as $process_callback) {
      $value = ResourceManager::executeCallback($process_callback, array($value));
    }
    return $value;
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
  protected function setPropertyValues(\EntityDrupalWrapper $wrapper, array $object, $replace = FALSE) {
    $save = FALSE;
    $original_object = $object;

    foreach ($this->fieldDefinitions as $public_field_name => $resource_field) {
      /** @var ResourceFieldEntityInterface $resource_field */
      if (!$this->fieldAccess($resource_field)) {
        // Allow passing the value in the request.
        unset($original_object[$public_field_name]);
        continue;
      }

      $property_name = $resource_field->getProperty();
      if (empty($property_name)) {
        // We may have for example an entity with no label property, but with a
        // label callback. In that case the $info['property'] won't exist, so
        // we skip this field.
        continue;
      }

      if (!$entity_property_access = $this->checkPropertyAccess('edit', $public_field_name, $wrapper->{$property_name}, $wrapper)) {
        throw new BadRequestException(format_string('Property @name cannot be set.', array('@name' => $public_field_name)));
      }
      if (!isset($object[$public_field_name])) {
        // No property to set in the request.
        if ($replace && $entity_property_access) {
          // We need to set the value to NULL.
          $wrapper->{$property_name}->set(NULL);
        }
        continue;
      }

      // Delegate modifications on the value of the field.
      $field_value = $resource_field->preprocess($object[$public_field_name]);

      $wrapper->{$property_name}->set($field_value);
      unset($original_object[$public_field_name]);
      $save = TRUE;
    }

    if (!$save) {
      // No request was sent.
      throw new BadRequestException('No values were sent with the request');
    }

    if ($original_object) {
      // Request had illegal values.
      $error_message = format_plural(count($original_object), 'Property @names is invalid.', 'Property @names are invalid.', array('@names' => implode(', ', array_keys($original_object))));
      throw new BadRequestException($error_message);
    }

    // Allow changing the entity just before it's saved. For example, setting
    // the author of the node entity.
    $this->entityPreSave($wrapper);

    $this->entityValidate($wrapper);

    $wrapper->save();
  }

}

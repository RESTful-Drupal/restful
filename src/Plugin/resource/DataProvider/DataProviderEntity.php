<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\DataProvider\DataProviderEntity.
 */

namespace Drupal\restful\Plugin\resource\DataProvider;

use Drupal\restful\Exception\ForbiddenException;
use Drupal\restful\Exception\InternalServerErrorException;
use Drupal\restful\Exception\ServerConfigurationException;
use Drupal\restful\Http\Request;
use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\Field\ResourceFieldBase;
use Drupal\restful\Plugin\resource\Field\ResourceFieldCollectionInterface;
use Drupal\restful\Plugin\resource\Field\ResourceFieldEntityInterface;

use Drupal\restful\Exception\BadRequestException;
use Drupal\restful\Exception\UnprocessableEntityException;
use Drupal\restful\Resource\ResourceManager;

class DataProviderEntity extends DataProvider {

  // TODO: The Data Provider should be in charge of the entity_access checks.

  /**
   * Field definitions.
   *
   * A collection of \Drupal\restful\Plugin\resource\Field\ResourceFieldEntityInterface[].
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
   * Determines the language of the items that should be returned.
   *
   * @var string
   */
  protected $langcode;

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
  public function __construct(RequestInterface $request, ResourceFieldCollectionInterface $field_definitions, $account, $options, $langcode) {
    parent::__construct($request, $field_definitions, $account, $options);
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
    $this->langcode = $langcode;

    // Make sure that all field definitions are instance of
    // \Drupal\restful\Plugin\resource\Field\ResourceFieldEntityInterface
    foreach ($this->fieldDefinitions as $key => $value) {
      if (!($value instanceof ResourceFieldEntityInterface)) {
        throw new ServerConfigurationException('The field mapping must implement \Drupal\restful\Plugin\resource\Field\ResourceFieldEntityInterface.');
      }
    }
  }

  /**
   * Get the language code.
   *
   * @return string
   */
  public function getLangCode() {
    return $this->langcode;
  }

  /**
   * Sets the language code.
   *
   * @param string $langcode
   *   The language code.
   */
  public function setLangCode($langcode) {
    $this->langcode = $langcode;
  }

  /**
   * Defines default sort fields if none are provided via the request URL.
   *
   * @return array
   *   Array keyed by the public field name, and the order ('ASC' or 'DESC') as value.
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

    // Pre-load all entities.
    entity_load($this->entityType, $ids);
  }

  /**
   * {@inheritdoc}
   */
  public function create($object) {

  }

  /**
   * {@inheritdoc}
   */
  public function view($identifier) {
    $id = $identifier;
    $entity_id = $this->getEntityIdByFieldId($id);

    if (!$this->isValidEntity('view', $entity_id)) {
      return;
    }

    /** @var \EntityDrupalWrapper $wrapper */
    $wrapper = entity_metadata_wrapper($this->entityType, $entity_id);
    $wrapper->language($this->getLangCode());
    $values = array();

    $limit_fields = !empty($request['fields']) ? explode(',', $request['fields']) : array();

    foreach ($this->fieldDefinitions as $resource_field_name => $info) {
      if ($limit_fields && !in_array($resource_field_name, $limit_fields)) {
        // Limit fields doesn't include this property.
        continue;
      }

      $value = NULL;

      if ($info['create_or_update_passthrough']) {
        // The public field is a dummy one, meant only for passing data upon
        // create or update.
        continue;
      }

      if ($info['callback']) {
        $value = ResourceManager::executeCallback($info['callback'], array($wrapper));
      }
      else {
        // Exposing an entity field.
        $property = $info['property'];
        $sub_wrapper = $info['wrapper_method_on_entity'] ? $wrapper : $wrapper->{$property};

        // Check user has access to the property.
        if ($property && !$this->checkPropertyAccess('view', $resource_field_name, $sub_wrapper, $wrapper)) {
          continue;
        }

        if (empty($info['formatter'])) {
          if ($sub_wrapper instanceof \EntityListWrapper) {
            // Multiple values.
            foreach ($sub_wrapper as $item_wrapper) {
              $value[] = $this->getValueFromProperty($wrapper, $item_wrapper, $info, $resource_field_name);
            }
          }
          else {
            // Single value.
            $value = $this->getValueFromProperty($wrapper, $sub_wrapper, $info, $resource_field_name);
          }
        }
        else {
          // Get value from field formatter.
          $value = $this->getValueFromFieldFormatter($wrapper, $sub_wrapper, $info);
        }
      }

      if ($value && $info['process_callbacks']) {
        foreach ($info['process_callbacks'] as $process_callback) {
          $value = ResourceManager::executeCallback($process_callback, array($value));
        }
      }

      $values[$resource_field_name] = $value;
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function viewEntity($id) {
    $entity_id = $this->getEntityIdByFieldId($id);
    $request = $this->getRequest();

    $cached_data = $this->getRenderedCache(array(
      'et' => $this->getEntityType(),
      'ei' => $entity_id,
    ));
    if (!empty($cached_data->data)) {
      return $cached_data->data;
    }

    if (!$this->isValidEntity('view', $entity_id)) {
      return;
    }

    $wrapper = entity_metadata_wrapper($this->entityType, $entity_id);
    $wrapper->language($this->getLangCode());
    $values = array();

    $limit_fields = !empty($request['fields']) ? explode(',', $request['fields']) : array();

    foreach ($this->getPublicFields() as $public_field_name => $info) {
      if ($limit_fields && !in_array($public_field_name, $limit_fields)) {
        // Limit fields doesn't include this property.
        continue;
      }

      $value = NULL;

      if ($info['create_or_update_passthrough']) {
        // The public field is a dummy one, meant only for passing data upon
        // create or update.
        continue;
      }

      if ($info['callback']) {
        $value = static::executeCallback($info['callback'], array($wrapper));
      }
      else {
        // Exposing an entity field.
        $property = $info['property'];
        $sub_wrapper = $info['wrapper_method_on_entity'] ? $wrapper : $wrapper->{$property};

        // Check user has access to the property.
        if ($property && !$this->checkPropertyAccess('view', $public_field_name, $sub_wrapper, $wrapper)) {
          continue;
        }

        if (empty($info['formatter'])) {
          if ($sub_wrapper instanceof \EntityListWrapper) {
            // Multiple values.
            foreach ($sub_wrapper as $item_wrapper) {
              $value[] = $this->getValueFromProperty($wrapper, $item_wrapper, $info, $public_field_name);
            }
          }
          else {
            // Single value.
            $value = $this->getValueFromProperty($wrapper, $sub_wrapper, $info, $public_field_name);
          }
        }
        else {
          // Get value from field formatter.
          $value = $this->getValueFromFieldFormatter($wrapper, $sub_wrapper, $info);
        }
      }

      if ($value && $info['process_callbacks']) {
        foreach ($info['process_callbacks'] as $process_callback) {
          $value = static::executeCallback($process_callback, array($value));
        }
      }

      $values[$public_field_name] = $value;
    }

    $this->setRenderedCache($values, array(
      'et' => $this->getEntityType(),
      'ei' => $entity_id,
    ));
    return $values;
  }


  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $identifiers) {

  }

  /**
   * {@inheritdoc}
   */
  public function update($identifier, $object, $replace = TRUE) {

  }

  /**
   * {@inheritdoc}
   */
  public function remove($identifier) {

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
    if (empty($request['loadByFieldName'])) {
      // The regular entity ID was provided.
      return $id;
    }
    $public_property_name = $request['loadByFieldName'];
    // We need to get the internal field/property from the public name.
    if ((!$public_field_info = $this->fieldDefinitions[$public_property_name]) || $public_field_info->getProperty()) {
      throw new BadRequestException(format_string('Cannot load an entity using the field "@name"', array(
        '@name' => $public_property_name,
      )));
    }
    $query = $this->getEntityFieldQuery();
    $query->range(0, 1);
    // Find out if the provided ID is a Drupal field or an entity property.
    if (static::propertyIsField($public_field_info['property'])) {
      $query->fieldCondition($public_field_info['property'], $public_field_info['column'], $id);
    }
    else {
      $query->propertyCondition($public_field_info['property'], $id);
    }

    // Execute the query and gather the results.
    $result = $query->execute();
    if (empty($result[$this->getEntityType()])) {
      throw new UnprocessableEntityException(format_string('The entity ID @id by @name for @resource cannot be loaded.', array(
        '@id' => $id,
        '@resource' => $this->getPluginKey('label'),
        '@name' => $public_property_name,
      )));
    }

    // There is nothing that guarantees that there is only one result, since
    // this is user input data. Return the first ID.
    $entity_id = key($result[$this->getEntityType()]);

    // REST requires a canonical URL for every resource.
    $this->addHttpHeaders('Link', $this->versionedUrl($entity_id, array(), FALSE) . '; rel="canonical"');

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
   * @param $op
   *   The operation to perform on the entity (view, update, delete).
   * @param $entity_id
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
   * @param \EntityMetadataWrapper $property_wrapper
   *   The wrapped property.
   * @param \EntityMetadataWrapper $wrapper
   *   The wrapped entity.
   *
   * @return bool
   *   TRUE if the current user has access to set the property, FALSE otherwise.
   */
  protected function checkPropertyAccess($op, $public_field_name, \EntityMetadataWrapper $property_wrapper, \EntityMetadataWrapper $wrapper) {
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
      $result = ResourceManager::executeCallback($callback, array($op, $public_field_name, $property_wrapper, $wrapper));

      if ($result == ResourceFieldBase::ACCESS_DENY) {
        return FALSE;
      }
    }

    return TRUE;
  }

}

<?php


/**
 * @file
 * Contains RestfulEntityBase.
 */

/**
 * An abstract implementation of RestfulEntityInterface.
 */
abstract class RestfulEntityBase extends RestfulBase implements RestfulEntityInterface {

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
   * The public fields that are exposed to the API.
   *
   * Array with the optional values:
   * - "access_callbacks": An array of callbacks to determine if user has access
   *   to the property. Note that this callback is on top of the access provided by
   *   entity API, and is used for convenience, where for example write
   *   operation on a property should be denied only on certain request
   *   conditions. The Passed arguments are:
   *   - op: The operation that access should be checked for. Can be "view" or
   *     "edit".
   *   - public_field_name: The name of the public field.
   *   - property_wrapper: The wrapped property.
   *   - wrapper: The wrapped entity.
   * - "property": The entity property (e.g. "title", "nid").
   * - "sub_property": A sub property name of a property to take from it the
   *   content. This can be used for example on a text field with filtered text
   *   input format where we would need to do $wrapper->body->value->value().
   *   Defaults to FALSE.
   * - "wrapper_method": The wrapper's method name to perform on the field.
   *   This can be used for example to get the entity label, by setting the
   *   value to "label". Defaults to "value".
   * - "wrapper_method_on_entity": A Boolean to indicate on what to perform
   *   the wrapper method. If TRUE the method will perform on the entity (e.g.
   *   $wrapper->label()) and FALSE on the property or sub property
   *   (e.g. $wrapper->field_reference->label()). Defaults to FALSE.
   * - "column": If the property is a field, set the column that would be used
   *   in queries. For example, the default column for a text field would be
   *   "value". Defaults to the first column returned by field_info_field(),
   *   otherwise FALSE.
   * - "callback": A callable callback to get a computed value. The wrapped
   *   entity is passed as argument. Defaults To FALSE.
   *   The callback function receive as first argument the entity
   *   EntityMetadataWrapper object.
   * - "process_callbacks": An array of callbacks to perform on the returned
   *   value, or an array with the object and method. Defaults To empty array.
   * - "resource": This property can be assigned only to an entity reference
   *   field. Array of restful resources keyed by the target bundle. For
   *   example, if the field is referencing a node entity, with "Article" and
   *   "Page" bundles, we are able to map those bundles to their related
   *   resource. Items with bundles that were not explicitly set would be
   *   ignored.
   *   It is also possible to pass an array as the value, with:
   *   - "name": The resource name.
   *   - "full_view": Determines if the referenced resource should be rendered,
   *   or just the referenced ID(s) to appear. Defaults to TRUE.
   *   array(
   *     // Shorthand.
   *     'article' => 'articles',
   *     // Verbose
   *     'page' => array(
   *       'name' => 'pages',
   *       'full_view' => FALSE,
   *     ),
   *   );
   *
   * @var array
   */
  protected $publicFields = array();

  /**
   * Determines the number of items that should be returned when viewing lists.
   *
   * @var int
   */
  protected $range = 50;

  /**
   * Set the pager range.
   *
   * @param int $range
   */
  public function setRange($range) {
    $this->range = $range;
  }

  /**
   * Get the pager range.
   *
   * @return int
   *  The range.
   */
  public function getRange() {
    return $this->range;
  }

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
   * {@inheritdoc}
   */
  public static function controllersInfo() {
    return array(
      '' => array(
        // GET returns a list of entities.
        \RestfulInterface::GET => 'getList',
        \RestfulInterface::HEAD => 'getList',
        // POST
        \RestfulInterface::POST => 'createEntity',
      ),
      '^(\d+,)*\d+$' => array(
        \RestfulInterface::GET => 'viewEntities',
        \RestfulInterface::HEAD => 'viewEntities',
        \RestfulInterface::PUT => 'putEntity',
        \RestfulInterface::PATCH => 'patchEntity',
        \RestfulInterface::DELETE => 'deleteEntity',
      ),
    );
  }

  /**
   * Constructs a RestfulEntityBase object.
   *
   * @param array $plugin
   *   Plugin definition.
   * @param RestfulAuthenticationManager $auth_manager
   *   (optional) Injected authentication manager.
   * @param DrupalCacheInterface $cache_controller
   *   (optional) Injected cache backend.
   */
  public function __construct($plugin, \RestfulAuthenticationManager $auth_manager = NULL, \DrupalCacheInterface $cache_controller = NULL) {
    parent::__construct($plugin, $auth_manager, $cache_controller);
    $this->entityType = $plugin['entity_type'];
    $this->bundle = $plugin['bundle'];
  }

  /**
   * Get a list of entities.
   *
   * @return array
   *   Array of entities, as passed to RestfulEntityBase::viewEntity().
   *
   * @throws RestfulBadRequestException
   */
  public function getList() {
    $request = $this->getRequest();
    $autocomplete_options = $this->getPluginInfo('autocomplete');
    if (!empty($autocomplete_options['enable']) && isset($request['autocomplete']['string'])) {
      // Return autocomplete list.
      return $this->getListForAutocomplete();
    }

    $entity_type = $this->entityType;
    $result = $this
      ->getQueryForList()
      ->execute();

    if (empty($result[$entity_type])) {
      return array();
    }

    $ids = array_keys($result[$entity_type]);

    // Pre-load all entities if there is no render cache.
    $cache_info = $this->getPluginInfo('cache');
    if (!$cache_info['render']) {
      entity_load($entity_type, $ids);
    }

    $return = array();

    foreach ($ids as $id) {
      $return[] = $this->viewEntity($id);
    }

    return $return;
  }

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
  public function viewEntities($entity_ids_string) {
    $entity_ids = array_unique(array_filter(explode(',', $entity_ids_string)));
    $output = array();

    foreach ($entity_ids as $entity_id) {
      $output[] = $this->viewEntity($entity_id);
    }
    return $output;
  }

  /**
   * Prepare a query for RestfulEntityBase::getList().
   *
   * @return EntityFieldQuery
   *   The EntityFieldQuery object.
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
   * Prepare a query for RestfulEntityBase::getTotalCount().
   *
   * @return EntityFieldQuery
   *   The EntityFieldQuery object.
   *
   * @throws RestfulBadRequestException
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
   * Helper method to get the total count of entities that match certain
   * request.
   *
   * @return int
   *   The total number of results without including pagination.
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
   * Return the values of the types tags, with the ID.
   *
   * @return array
   *   Array with the found terms keys by the entity ID.
   *   ID. Otherwise, if the field allows auto-creating tags, the ID will be the
   *   term name, to indicate for client it is an unsaved term.
   *
   * @see taxonomy_autocomplete()
   *
   * @throws \Exception
   */
  protected function getListForAutocomplete() {
    $entity_info = entity_get_info($this->getEntityType());
    if (empty($entity_info['entity keys']['label'])) {
      // Entity is invalid for autocomplete, as it doesn't have a "label"
      // property.
      $params = array('@entity' => $this->getEntityType());
      throw new \Exception(format_string('Cannot autocomplete @entity as it does not have a "label" property defined.', $params));
    }

    $request = $this->getRequest();
    if (empty($request['autocomplete']['string'])) {
      // Empty string.
      return array();
    }

    $result = $this->getQueryResultForAutocomplete();

    $return = array();
    foreach ($result as $entity_id => $label) {
      $return[$entity_id] = check_plain($label);
    }

    return $return;
  }

  /**
   * Return the bundles that should be used for the autocomplete search.
   *
   * @return array
   *   Array with the bundle name(s).
   */
  protected function getBundlesForAutocomplete() {
    return array($this->getBundle());
  }

  /**
   * Request the query object to get a list for autocomplete.
   *
   * @return EntityFieldQuery
   *   Return a query object, before it is executed.
   */
  protected function getQueryForAutocomplete() {
    $autocomplete_options = $this->getPluginInfo('autocomplete');
    $entity_type = $this->getEntityType();
    $entity_info = entity_get_info($entity_type);
    $request = $this->getRequest();

    $string = drupal_strtolower($request['autocomplete']['string']);
    $operator = !empty($request['autocomplete']['operator']) ? $request['autocomplete']['operator'] : $autocomplete_options['operator'];

    $query = new EntityFieldQuery();

    $query->entityCondition('entity_type', $entity_type);
    if ($bundles = $this->getBundlesForAutocomplete()) {
      $query->entityCondition('bundle', $bundles, 'IN');
    }

    $query->propertyCondition($entity_info['entity keys']['label'], $string, $operator);

    // Add a generic entity access tag to the query.
    $query->addTag($entity_type . '_access');
    $query->addTag('restful');
    $query->addMetaData('restful_handler', $this);
    $query->addMetaData('account', $this->getAccount());

    // Sort by label.
    $query->propertyOrderBy($entity_info['entity keys']['label']);

    // Add range.
    $query->range(0, $autocomplete_options['range']);

    return $query;
  }

  /**
   * Returns the result of a query for the auto complete.
   *
   * @return array
   *   Array keyed by the entity ID and the unsanitized entity label as value.
   */
  protected function getQueryResultForAutocomplete() {
    $entity_type = $this->getEntityType();
    $query = $this->getQueryForAutocomplete();
    $result = $query->execute();

    if (empty($result[$entity_type])) {
      // No entities found.
      return array();
    }

    $ids = array_keys($result[$entity_type]);
    $return = array();

    foreach (entity_load($entity_type, $ids) as $id => $entity) {
      $return[$id] = entity_label($entity_type, $entity);
    }

    return $return;
  }

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
  public function viewEntity($entity_id) {
    $request = $this->getRequest();

    $cached_data = $this->getRenderedCache(array(
      'et' => $this->getEntityType(),
      'ei' => $entity_id,
    ));
    if (!empty($cached_data->data)) {
      return $cached_data->data;
    }

    $this->isValidEntity('view', $entity_id);

    $wrapper = entity_metadata_wrapper($this->entityType, $entity_id);
    $values = array();

    $limit_fields = !empty($request['fields']) ? explode(',', $request['fields']) : array();

    foreach ($this->getPublicFields() as $public_field_name => $info) {
      if ($limit_fields && !in_array($public_field_name, $limit_fields)) {
        // Limit fields doesn't include this property.
        continue;
      }

      $value = NULL;

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

        $method = $info['wrapper_method'] ? $info['wrapper_method'] : NULL;
        $resource = $info['resource'] ? $info['resource'] : NULL;

        if ($sub_wrapper instanceof EntityListWrapper) {
          // Multiple value.
          foreach ($sub_wrapper as $item_wrapper) {
            if ($info['sub_property'] && $item_wrapper->value()) {
              $item_wrapper = $item_wrapper->{$info['sub_property']};
            }

            if ($resource) {
              if ($value_from_resource = $this->getValueFromResource($item_wrapper, $property, $resource)) {
                $value[] = $value_from_resource;
              }
            }
            else {
              // Wrapper method.
              $value[] = $item_wrapper->{$method}();
            }
          }
        }
        else {
          // Single value.
          if ($info['sub_property'] && $sub_wrapper->value()) {
            $sub_wrapper = $sub_wrapper->{$info['sub_property']};
          }

          if ($resource) {
            $value = $this->getValueFromResource($sub_wrapper, $property, $resource);
          }
          else {
            // Wrapper method.
            $value = $sub_wrapper->{$method}();
          }
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
   * Get the "target_type" property from an entity reference field.
   *
   * @param $property
   *   The field name.
   * @return string
   *   The target type of the referenced entity.
   *
   * @throws Exception
   *   Errors is the passed field name is invalid.
   */
  protected function getTargetTypeFromEntityReference($property) {
    if (!$field = field_info_field($property)) {
      throw new Exception('Property is not a field.');
    }

    if ($field['type'] == 'entityreference') {
      return $field['settings']['target_type'];
    }
    elseif ($field['type'] == 'taxonomy_term_reference') {
      return 'taxonomy_term';
    }

    throw new Exception('Property is not an entity reference field.');

  }

  /**
   * Get value from an entity reference field with "resource" property.
   *
   * @param EntityMetadataWrapper $wrapper
   *   The wrapped object.
   * @param string $property
   *   The property name (i.e. the field name).
   * @param array $resource
   *   Array with resource names, keyed by the bundle name.
   *
   * @return mixed
   *   The value if found, or NULL if bundle not defined.
   */
  protected function getValueFromResource(EntityMetadataWrapper $wrapper, $property, $resource) {
    $handlers = &drupal_static(__FUNCTION__, array());

    if (!$entity = $wrapper->value()) {
      return;
    }

    $target_type = $this->getTargetTypeFromEntityReference($property);
    list($id,, $bundle) = entity_extract_ids($target_type, $entity);

    if (empty($resource[$bundle])) {
      // Bundle not mapped to a resource.
      return;
    }

    if (!$resource[$bundle]['full_view']) {
      // Show only the ID(s) of the referenced resource.
      return $wrapper->value(array('identifier' => TRUE));
    }


    if (empty($handlers[$bundle])) {
      $version = $this->getVersion();
      $handlers[$bundle] = restful_get_restful_handler($resource[$bundle]['name'], $version['major'], $version['minor']);
    }
    $bundle_handler = $handlers[$bundle];
    return $bundle_handler->viewEntity($id);
  }

  /**
   * Update an entity using PUT.
   *
   * Non existing properties are assumed to be equal to NULL.
   *
   * @param $entity_id
   *   The entity ID.
   *
   * @return array
   *   Array with the output of the new entity, passed to
   *   RestfulEntityInterface::viewEntity().
   */
  public function putEntity($entity_id) {
    return $this->updateEntity($entity_id, TRUE);
  }

  /**
   * Update an entity using PATCH.
   *
   * Non existing properties are skipped.
   *
   * @param $entity_id
   *   The entity ID.
   *
   * @return array
   *   Array with the output of the new entity, passed to
   *   RestfulEntityInterface::viewEntity().
   */
  public function patchEntity($entity_id) {
    return $this->updateEntity($entity_id, FALSE);
  }

  /**
   * Delete an entity using DELETE.
   *
   * No result is returned, just the HTTP header is set to 204.
   *
   * @param $entity_id
   *   The entity ID.
   */
  public function deleteEntity($entity_id) {
    $this->isValidEntity('update', $entity_id);

    $wrapper = entity_metadata_wrapper($this->entityType, $entity_id);
    $wrapper->delete();

    // Set the HTTP headers.
    $this->setHttpHeaders('Status', 204);
  }

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
  protected function updateEntity($entity_id, $null_missing_fields = FALSE) {
    $this->isValidEntity('update', $entity_id);

    $wrapper = entity_metadata_wrapper($this->entityType, $entity_id);

    $this->setPropertyValues($wrapper, $null_missing_fields);

    // Set the HTTP headers.
    $this->setHttpHeaders('Status', 201);

    if (!empty($wrapper->url) && $url = $wrapper->url->value()); {
      $this->setHttpHeaders('Location', $url);
    }

    return array($this->viewEntity($wrapper->getIdentifier()));
  }


  /**
   * Create a new entity.
   *
   * @return array
   *   Array with the output of the new entity, passed to
   *   RestfulEntityInterface::viewEntity().
   *
   * @throws RestfulForbiddenException
   */
  public function createEntity() {
    $entity_info = entity_get_info($this->entityType);
    $bundle_key = $entity_info['entity keys']['bundle'];
    $values = array($bundle_key => $this->bundle);

    $entity = entity_create($this->entityType, $values);

    if ($this->checkEntityAccess('create', $this->entityType, $entity) === FALSE) {
      // User does not have access to create entity.
      $params = array('@resource' => $this->plugin['label']);
      throw new RestfulForbiddenException(format_string('You do not have access to create a new @resource resource.', $params));
    }

    $wrapper = entity_metadata_wrapper($this->entityType, $entity);

    $this->setPropertyValues($wrapper);
    return array($this->viewEntity($wrapper->getIdentifier()));
  }

  /**
   * Set properties of the entity based on the request, and save the entity.
   *
   * @param EntityMetadataWrapper $wrapper
   *   The wrapped entity object, passed by reference.
   * @param bool $null_missing_fields
   *   Determine if properties that are missing form the request array should
   *   be treated as NULL, or should be skipped. Defaults to FALSE, which will
   *   set the fields to NULL.
   *
   * @throws RestfulBadRequestException
   */
  protected function setPropertyValues(EntityMetadataWrapper $wrapper, $null_missing_fields = FALSE) {
    $request = $this->getRequest();

    static::cleanRequest($request);
    $save = FALSE;
    $original_request = $request;

    foreach ($this->getPublicFields() as $public_field_name => $info) {
      if (empty($info['property'])) {
        // We may have for example an entity with no label property, but with a
        // label callback. In that case the $info['property'] won't exist, so
        // we skip this field.
        continue;
      }

      $property_name = $info['property'];
      if (!isset($request[$public_field_name])) {
        // No property to set in the request.
        if ($null_missing_fields && $this->checkPropertyAccess('edit', $public_field_name, $wrapper->{$property_name}, $wrapper)) {
          // We need to set the value to NULL.
          $wrapper->{$property_name}->set(NULL);
        }
        continue;
      }

      if (!$this->checkPropertyAccess($public_field_name, 'edit', $wrapper->{$property_name}, $wrapper)) {
        throw new RestfulBadRequestException(format_string('Property @name cannot be set.', array('@name' => $public_field_name)));
      }

      $field_value = $this->propertyValuesPreprocess($property_name, $request[$public_field_name], $public_field_name);

      $wrapper->{$property_name}->set($field_value);
      unset($original_request[$public_field_name]);
      $save = TRUE;
    }

    if (!$save) {
      // No request was sent.
      throw new RestfulBadRequestException('No values were sent with the request');
    }

    if ($original_request) {
      // Request had illegal values.
      $error_message = format_plural(count($original_request), 'Property @names is invalid.', 'Property @names are invalid.', array('@names' => implode(', ', array_keys($original_request))));
      throw new RestfulBadRequestException($error_message);
    }

    // Allow changing the entity just before it's saved. For example, setting
    // the author of the node entity.
    $this->entityPreSave($wrapper);

    $this->entityValidate($wrapper);

    $wrapper->save();
  }

  /**
   * Massage the value to set according to the format expected by the wrapper.
   *
   * @param string $property_name
   *   The property name to set.
   * @param $value
   *   The value passed in the request.
   * @param string $public_field_name
   *   The name of the public field to set.
   *
   * @return mixed
   *   The value to set using the wrapped property.
   */
  public function propertyValuesPreprocess($property_name, $value, $public_field_name) {
    // Get the field info.
    $field_info = field_info_field($property_name);

    switch ($field_info['type']) {
      case 'entityreference':
      case 'taxonomy_term_reference':
        return $this->propertyValuesPreprocessReference($property_name, $value, $field_info, $public_field_name);

      case 'text':
      case 'text_long':
      case 'text_with_summary':
        return $this->propertyValuesPreprocessText($property_name, $value, $field_info);

      case 'file':
      case 'image':
        return $this->propertyValuesPreprocessFile($property_name, $value, $field_info);
    }

    // Return the value as is.
    return $value;
  }

  /**
   * Pre-process value for "Entity reference" field types.
   *
   * @param string $property_name
   *   The property name to set.
   * @param $value
   *   The value passed in the request.
   * @param array $field_info
   *   The field info array.
   * @param string $public_field_name
   *   The name of the public field to set.
   *
   * @return mixed
   *   The value to set using the wrapped property.
   */
  protected function propertyValuesPreprocessReference($property_name, $value, $field_info, $public_field_name) {
    if ($field_info['cardinality'] != 1 && !is_array($value)) {
      // If the field is entity reference type and its cardinality larger than
      // 1 set value to an array.
      $value = explode(',', $value);
    }

    $value = $this->createEntityFromReference($property_name, $value, $field_info, $public_field_name);

    return $value;
  }

  /**
   * Helper function; Create an entity from a a sub-resource.
   *
   * @param string $property_name
   *   The property name to set.
   * @param $value
   *   The value passed in the request.
   * @param array $field_info
   *   The field info array.
   * @param string $public_field_name
   *   The name of the public field to set.
   *
   * @return mixed
   *   The value to set using the wrapped property.
   */
  protected function createEntityFromReference($property_name, $value, $field_info, $public_field_name) {
    $public_fields = $this->getPublicFields();

    if (empty($public_fields[$public_field_name]['resource'])) {
      // Field is not defined as "resource", which means it only accepts an
      // integer as a valid value.
      return $value;
    }

    if ($field_info['cardinality'] == 1 && !is_array($value)) {
      return $value;
    }

    // In case we have multiple bundles, we opt for the first one.
    $resource = reset($public_fields[$public_field_name]['resource']);
    $resource_name = $resource['name'];

    $version = $this->getVersion();
    $handler = restful_get_restful_handler($resource_name, $version['major'], $version['minor']);

    // Return the entity ID that was created.
    if ($field_info['cardinality'] == 1) {
      // Single value.
      return $this->createOrUpdateSubResourceItem($value, $handler);
    }

    // Multiple values.
    $return = array();
    foreach ($value as $value_item) {
      $return[] = $this->createOrUpdateSubResourceItem($value_item, $handler);
    }

    return $return;
  }

  /**
   * Create, update or return an already saved entity.
   *
   * @param int | array $value
   *   The entity ID, or array to POST, PATCH, or PUT entity from.
   * @param \RestfulInterface $handler
   *   The RESTful handler.
   *
   * @return int
   *   The saved entity ID.
   */
  protected function createOrUpdateSubResourceItem($value, $handler) {
    if (!is_array($value)) {
      // Item that was passed is already a reference to an existing entity.
      return $value;
    }

    // Value is actually a sub request, so for clarity we will name it $request.
    $request = $value;

    // Figure the method that should be used.
    if (empty($request['id'])) {
      $method_name = \RestfulInterface::POST;
      $path = '';
    }
    else {
      // Use PATCH by default, unless client has explicitly set the method in
      // the sub-resource.
      // As any request, under the the "__application" we may pass additional
      // metadata.
      $method_name = !empty($request['__application']['method']) ? strtoupper($request['__application']['method']) : \RestfulInterface::PATCH;
      $path = implode(',', array_unique(array_filter(explode(',', $request['id']))));
      // Unset the ID from the sub-request.
      unset($request['id']);
    }

    $result = $handler->process($path, $request, $method_name, FALSE);
    return $result[0]['id'];
  }

  /**
   * Preprocess value for "Text" related field types.
   *
   * @param string $property_name
   *   The property name to set.
   * @param $value
   *   The value passed in the request.
   * @param array $field_info
   *   The field info array.
   *
   * @return mixed
   *   The value to set using the wrapped property.
   */
  protected function propertyValuesPreprocessText($property_name, $value, $field_info) {
    // Text field. Check if field has an input format.
    $instance = field_info_instance($this->getEntityType(), $property_name, $this->getBundle());

    if ($field_info['cardinality'] == 1) {
      // Single value.
      if (!$instance['settings']['text_processing']) {
        return $value;
      }

      return array (
        'value' => $value,
        'format' => 'filtered_html',
      );
    }

    // Multiple values.
    foreach ($value as $delta => $single_value) {
      if (!$instance['settings']['text_processing']) {
        $return[$delta] = $single_value;
      }
      else {
        $return[$delta] = array(
          'value' => $single_value,
          'format' => 'filtered_html',
        );
      }
    }
    return $return;
  }

  /**
   * Preprocess value for "File" related field types.
   *
   * @param string $property_name
   *   The property name to set.
   * @param $value
   *   The value passed in the request.
   * @param array $field_info
   *   The field info array.
   *
   * @return mixed
   *   The value to set using the wrapped property.
   */
  protected function propertyValuesPreprocessFile($property_name, $value, $field_info) {
    if ($field_info['cardinality'] == 1) {
      // Single value.
      return array(
        'fid' => $value,
        'display' => TRUE,
      );
    }

    $value = is_array($value) ? $value : explode(',', $value);
    $return = array();
    foreach ($value as $delta => $single_value) {
      $return[$delta] = array(
        'fid' => $single_value,
        'display' => TRUE,
      );
    }
    return $return;
  }

  /**
   * Allow manipulating the entity before it is saved.
   *
   * @param \EntityMetadataWrapper $wrapper
   *   The unsaved wrapped entity.
   */
  public function entityPreSave(\EntityMetadataWrapper $wrapper) {}


  /**
   * Validate an entity before it is saved.
   *
   * @param \EntityMetadataWrapper $wrapper
   *   The wrapped entity.
   *
   * @throws \RestfulBadRequestException
   */
  public function entityValidate(\EntityMetadataWrapper $wrapper) {
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
    foreach ($this->getPublicFields() as $field_name => $value) {
      if (empty($value['property'])) {
        continue;
      }

      if (empty($errors[$value['property']])) {
        // Field validated.
        continue;
      }

      $map[$value['property']] = $field_name;
      $params['@fields'][] = $field_name;
    }

    if (empty($params['@fields'])) {
      // There was a validation error, but on non-public fields, so we need to
      // throw an exception, but can't say on which fields it occurred.
      throw new \RestfulBadRequestException('Invalid value(s) sent with the request.');
    }

    $params['@fields'] = implode(',', $params['@fields']);
    $e = new \RestfulBadRequestException(format_plural(count($map), 'Invalid value in field @fields.', 'Invalid values in fields @fields.', $params));
    foreach ($errors as $property_name => $messages) {
      if (empty($map[$property_name])) {
        // Entity is not valid, but on a field not public.
        continue;
      }

      $field_name = $map[$property_name];

      foreach ($messages as $message) {

        $message['params']['@field'] = $field_name;
        $output = format_string($message['message'], $message['params']);

        $e->addFieldError($field_name, $output);
      }
    }

    // Throw the exception.
    throw $e;
  }

  /**
   * Check access on a property.
   *
   * @param string $op
   *   The operation that access should be checked for. Can be "view" or "edit".
   *   Defaults to "edit".
   * @param string $public_field_name
   *   The name of the public field.
   * @param EntityMetadataWrapper $property_wrapper
   *   The wrapped property.
   * @param EntityMetadataWrapper $wrapper
   *   The wrapped entity.
   *
   * @return bool
   *   TRUE if the current user has access to set the property, FALSE otherwise.
   */
  protected function checkPropertyAccess($op, $public_field_name, EntityMetadataWrapper $property_wrapper, EntityMetadataWrapper $wrapper) {
    if (!$this->checkPropertyAccessByAccessCallbacks($public_field_name, $op, $property_wrapper, $wrapper)) {
      // Access callbacks denied access.
      return;
    }

    $account = $this->getAccount();
    // Check format access for text fields.
    if ($property_wrapper->type() == 'text_formatted' && $property_wrapper->value() && $property_wrapper->format->value()) {
      $format = (object) array('format' => $property_wrapper->format->value());
      if (!filter_access($format, $account)) {
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
   * @param EntityMetadataWrapper $property_wrapper
   *   The wrapped property.
   * @param EntityMetadataWrapper $wrapper
   *   The wrapped entity.
   *
   * @return bool
   *   TRUE if the current user has access to set the property, FALSE otherwise.
   *   The default implementation assumes that if no callback has explicitly
   *   denied access, we grant the user permission.
   */
  protected function checkPropertyAccessByAccessCallbacks($op, $public_field_name, EntityMetadataWrapper $property_wrapper, EntityMetadataWrapper $wrapper) {
    $public_fields = $this->getPublicFields();

    foreach ($public_fields[$public_field_name]['access_callbacks'] as $callback) {
      $result = static::executeCallback($callback, array($op, $public_field_name, $property_wrapper, $wrapper));

      if ($result == \RestfulInterface::ACCESS_DENY) {
        return FALSE;
      }
    }

    return TRUE;
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
   * @throws RestfulUnprocessableEntityException
   * @throws RestfulForbiddenException
   */
  protected function isValidEntity($op, $entity_id) {
    $entity_type = $this->entityType;

    $params = array(
      '@id' => $entity_id,
      '@resource' => $this->plugin['label'],
    );

    if (!$entity = entity_load_single($entity_type, $entity_id)) {
      throw new RestfulUnprocessableEntityException(format_string('The entity ID @id for @resource does not exist.', $params));
    }

    list(,, $bundle) = entity_extract_ids($entity_type, $entity);

    $resource_bundle = $this->getBundle();
    if ($resource_bundle && $bundle != $resource_bundle) {
      throw new RestfulUnprocessableEntityException(format_string('The entity ID @id is not a valid @resource.', $params));
    }

    if ($this->checkEntityAccess($op, $entity_type, $entity) === FALSE) {
      // Entity was explicitly denied.
      throw new RestfulForbiddenException(format_string('You do not have access to entity ID @id of resource @resource', $params));
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
   * {@inheritdoc}
   */
  public function publicFieldsInfo() {
    $entity_info = entity_get_info($this->getEntityType());
    $id_key = $entity_info['entity keys']['id'];

    $public_fields = array(
      'id' => array(
        'wrapper_method' => 'getIdentifier',
        'wrapper_method_on_entity' => TRUE,
        'property' => $id_key,
      ),
      'label' => array(
        'wrapper_method' => 'label',
        'wrapper_method_on_entity' => TRUE,
      ),
      'self' => array('property' => 'url'),
    );

    if (!empty($entity_info['entity keys']['label'])) {
      $public_fields['label']['property'] = $entity_info['entity keys']['label'];
    }

    return $public_fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getPublicFields() {
    if ($this->publicFields) {
      // Return early.
      return $this->publicFields;
    }

    // Get the public fields that were defined by the user.
    $public_fields = $this->publicFieldsInfo();

    // Set defaults values.
    foreach (array_keys($public_fields) as $key) {
      // Set default values.
      $info = &$public_fields[$key];
      $info += array(
        'access_callbacks' => array(),
        'callback' => FALSE,
        'column' => FALSE,
        'process_callbacks' => array(),
        'property' => FALSE,
        'resource' => array(),
        'sub_property' => FALSE,
        'wrapper_method' => 'value',
        'wrapper_method_on_entity' => FALSE,
      );

      if ($field = field_info_field($info['property'])) {
        // If it's an image check if we need to add image style processing.
        if ($field['type'] == 'image' && !empty($info['image_styles'])) {
          array_unshift($info['process_callbacks'], array(array($this, 'getImageUris'), array($info['image_styles'])));
        }
        if ($info['property'] && !$info['column']) {
          // Set the column name.
          $info['column'] = key($field['columns']);
        }
      }

      foreach ($info['resource'] as &$resource) {
        // Expand array to be verbose.
        if (!is_array($resource)) {
          $resource = array('name' => $resource);
        }

        // Set default value.
        $resource += array('full_view' => TRUE);
      }
    }

    // Cache the processed fields.
    $this->setPublicFields($public_fields);

    return $public_fields;
  }

  /**
   * Set the public fields.
   *
   * @param array $public_fields
   *   The processed public fields array.
   */
  public function setPublicFields(array $public_fields = array()) {
    $this->publicFields = $public_fields;
  }

  /**
   * Helper method to know if the current request is for a list of entities.
   *
   * @return boolean
   *   TRUE if the request is for a list. FALSE otherwise.
   */
  public function isListRequest() {
    if ($this->getMethod() != \RestfulInterface::GET) {
      return FALSE;
    }
    $path = $this->getPath();
    return empty($path) || strpos($path, ',') !== FALSE;
  }

  /**
   * Get the image URLs based on the configured image styles.
   *
   * @param array $file_array
   *   The file array.
   * @param array $image_styles
   *   The list of image styles to use.
   *
   * @return array
   *   The input file array with an extra key for the image styles.
   */
  public function getImageUris(array $file_array, $image_styles) {
    // Return early if there are no image styles.
    if (empty($image_styles)) {
      return $file_array;
    }
    // If $file_array is an array of file arrays. Then call recursively for each
    // item and return the result.
    if (static::isArrayNumeric($file_array)) {
      $output = array();
      foreach ($file_array as $item) {
        $output[] = $this->getImageUris($item, $image_styles);
      }
      return $output;
    }
    $file_array['image_styles'] = array();
    foreach ($image_styles as $style) {
      $file_array['image_styles'][$style] = image_style_url($style, $file_array['uri']);
    }
    return $file_array;
  }

  /**
   * Helper method to determine if an array is numeric.
   *
   * @param array $input
   *   The input array.
   *
   * @return boolean
   *   TRUE if the array is numeric, false otherwise.
   */
  protected final static function isArrayNumeric(array $input) {
    foreach (array_keys($input) as $key) {
      if (!ctype_digit((string) $key)) {
        return FALSE;
      }
    }
    return TRUE;
  }

}

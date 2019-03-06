<?php


/**
 * @file
 * Contains RestfulEntityBase.
 */

/**
 * An abstract implementation of RestfulEntityInterface.
 */
abstract class RestfulEntityBase extends \RestfulDataProviderEFQ implements \RestfulEntityInterface {

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
   * - "formatter": Used for rendering the value of a configurable field using
   *   Drupal field API's formatter. The value is the $display value that is
   *   passed to field_view_field().
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
   * - "create_or_update_passthrough": Determines if a public field that isn't
   *   mapped to any property or field, may be passed upon create or update
   *   of an entity. Defaults to FALSE.
   *
   * @var array
   */
  protected $publicFields = array();

  /**
   * Overrides \RestfulDataProviderEFQ::controllersInfo().
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
      '^.*$' => array(
        \RestfulInterface::GET => 'viewEntities',
        \RestfulInterface::HEAD => 'viewEntities',
        \RestfulInterface::PUT => 'putEntity',
        \RestfulInterface::PATCH => 'patchEntity',
        \RestfulInterface::DELETE => 'deleteEntity',
      ),
    );
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
    $autocomplete_options = $this->getPluginKey('autocomplete');
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
    $cache_info = $this->getPluginKey('render_cache');
    if (!$cache_info['render']) {
      entity_load($entity_type, $ids);
    }

    $return = array();

    // If no IDs were requested, we should not throw an exception in case an
    // entity is un-accessible by the user.
    foreach ($ids as $id) {
      if ($row = $this->viewEntity($id)) {
        $return[] = $row;
      }
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function viewEntities($ids_string) {
    $ids = array_unique(array_filter(explode(',', $ids_string)));
    $output = array();

    foreach ($ids as $id) {
      $output[] = $this->viewEntity($id);
    }
    return $output;
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
    $entity_info = $this->getEntityInfo();
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
    $info = $this->getEntityInfo();
    // When a bundle key wasn't defined return false in order to make the
    // autocomplete support entities without bundle key. i.e: user, vocabulary.
    $bundle = $this->getBundle();
    return !empty($bundle) && !empty($info['entity keys']['bundle']) ? array($bundle) : FALSE;
  }

  /**
   * Request the query object to get a list for autocomplete.
   *
   * @return EntityFieldQuery
   *   Return a query object, before it is executed.
   */
  protected function getQueryForAutocomplete() {
    $autocomplete_options = $this->getPluginKey('autocomplete');
    $entity_type = $this->getEntityType();
    $entity_info = $this->getEntityInfo();
    $request = $this->getRequest();

    $string = drupal_strtolower($request['autocomplete']['string']);
    $operator = !empty($request['autocomplete']['operator']) ? $request['autocomplete']['operator'] : $autocomplete_options['operator'];

    $query = $this->EFQObject();

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
   * {@inheritdoc}
   */
  public function viewEntity($id) {
    $entity_id = $this->getEntityIdByFieldId($id);
    $request = $this->getRequest();

    $cached_data = $this->getRenderedCache($this->getEntityCacheTags($entity_id));
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
          if ($sub_wrapper instanceof EntityListWrapper) {
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

      if (isset($value) && $info['process_callbacks']) {
        foreach ($info['process_callbacks'] as $process_callback) {
          $value = static::executeCallback($process_callback, array($value));
        }
      }

      $values[$public_field_name] = $value;
    }

    $this->setRenderedCache($values, $this->getEntityCacheTags($entity_id));
    return $values;
  }

  /**
   * The array of parameters by which entities should be cached.
   *
   * @param mixed $entity_id
   *   The entity ID of the entity to be cached.
   *
   * @return array
   *   An array of parameter keys and values which should be added
   *   to the cache key for each entity.
   */
  public function getEntityCacheTags($entity_id) {
    return array(
      'et' => $this->getEntityType(),
      'ei' => $entity_id,
    );
  }

  /**
   * Get value from a property.
   *
   * @param EntityMetadataWrapper $wrapper
   *   The wrapped entity.
   * @param EntityMetadataWrapper $sub_wrapper
   *   The wrapped property.
   * @param array $info
   *   The public field info array.
   * @param $public_field_name
   *   The field name.
   *
   * @return mixed
   *   A single or multiple values.
   */
  protected function getValueFromProperty(\EntityMetadataWrapper $wrapper, \EntityMetadataWrapper $sub_wrapper, array $info, $public_field_name) {
    $property = $info['property'];
    $method = $info['wrapper_method'];
    $resource = $info['resource'] ?: NULL;

    if ($info['sub_property'] && $sub_wrapper->value()) {
      $sub_wrapper = $sub_wrapper->{$info['sub_property']};
    }

    if ($resource) {
      $value = $this->getValueFromResource($sub_wrapper, $property, $resource, $public_field_name, $wrapper->getIdentifier());
    }
    else {
      // Wrapper method.
      $value = $sub_wrapper->{$method}();
    }

    return $value;
  }

  /**
   * Get value from a field rendered by Drupal field API's formatter.
   *
   * @param EntityMetadataWrapper $wrapper
   *   The wrapped entity.
   * @param EntityMetadataWrapper $sub_wrapper
   *   The wrapped property.
   * @param array $info
   *   The public field info array.
   *
   * @return mixed
   *   A single or multiple values.
   */
  protected function getValueFromFieldFormatter(\EntityMetadataWrapper $wrapper, \EntityMetadataWrapper $sub_wrapper, array $info) {
    $property = $info['property'];

    if (!static::propertyIsField($property)) {
      // Property is not a field.
      throw new \RestfulServerConfigurationException(format_string('@property is not a configurable field, so it cannot be processed using field API formatter', array('@property' => $property)));
    }

    // Get values from the formatter.
    $output = field_view_field($this->getEntityType(), $wrapper->value(), $property, $info['formatter']);

    // Unset the theme, as we just want to get the value from the formatter,
    // without the wrapping HTML.
    unset($output['#theme']);


    if ($sub_wrapper instanceof EntityListWrapper) {
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
   * Get the "target_type" property from an field or property reference.
   *
   * @param \EntityMetadataWrapper $wrapper
   *   The wrapped property.
   * @param $property
   *   The public field name.
   *
   * @return string
   *   The target type of the referenced entity.
   *
   * @throws \RestfulException
   */
  protected function getTargetTypeFromEntityReference(\EntityMetadataWrapper $wrapper, $property) {
    $params = array('@property' => $property);

    if ($field = field_info_field($property)) {
      if ($field['type'] == 'entityreference') {
        return $field['settings']['target_type'];
      }
      elseif ($field['type'] == 'taxonomy_term_reference') {
        return 'taxonomy_term';
      }
      elseif ($field['type'] == 'field_collection') {
        return 'field_collection_item';
      }
      elseif ($field['type'] == 'commerce_product_reference') {
        return 'commerce_product';
      }
      elseif ($field['type'] == 'commerce_line_item_reference') {
        return 'commerce_line_item';
      }
      elseif ($field['type'] == 'node_reference') {
        return 'node';
      }

      throw new \RestfulException(format_string('Field @property is not an entity reference or taxonomy reference field.', $params));
    }
    else {
      // This is a property referencing another entity (e.g. the "uid" on the
      // node object).
      $info = $wrapper->info();
      if ($this->getEntityInfo($info['type'])) {
        return $info['type'];
      }

      throw new \RestfulException(format_string('Property @property is not defined as reference in the EntityMetadataWrapper definition.', $params));
    }
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
   * @param string $public_field_name
   *   Field name in the output. This is used to store additional metadata
   *   useful for the formatter.
   * @param int $host_id
   *   Host entity ID. Used to structure the value metadata.
   *
   * @return mixed
   *   The value if found, or NULL if bundle not defined.
   */
  protected function getValueFromResource(EntityMetadataWrapper $wrapper, $property, $resource, $public_field_name = NULL, $host_id = NULL) {
    $handlers = $this->staticCache->get(__CLASS__ . '::' . __FUNCTION__, array());

    if (!$entity = $wrapper->value()) {
      return;
    }

    $target_type = $this->getTargetTypeFromEntityReference($wrapper, $property);
    list($id,, $bundle) = entity_extract_ids($target_type, $entity);

    if (empty($resource[$bundle])) {
      // Bundle not mapped to a resource.
      return;
    }

    if (!$resource[$bundle]['full_view']) {
      // Show only the ID(s) of the referenced resource.
      return $wrapper->value(array('identifier' => TRUE));
    }

    if ($public_field_name) {
      $this->valueMetadata[$host_id][$public_field_name][] = array(
        'id' => $id,
        'entity_type' => $target_type,
        'bundle' => $bundle,
        'resource_name' => $resource[$bundle]['name'],
      );
    }

    if (empty($handlers[$bundle])) {
      $handlers[$bundle] = restful_get_restful_handler($resource[$bundle]['name'], $resource[$bundle]['major_version'], $resource[$bundle]['minor_version']);
    }
    $bundle_handler = $handlers[$bundle];

    // Pipe the parent request and account to the sub-request.
    $piped_request = $this->getRequestForSubRequest();
    $bundle_handler->setAccount($this->getAccount());
    $bundle_handler->setRequest($piped_request);
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
   * {@inheritdoc}
   */
  public function deleteEntity($entity_id) {
    $this->isValidEntity('delete', $entity_id);

    $wrapper = entity_metadata_wrapper($this->entityType, $entity_id);
    $wrapper->delete();

    // Set the HTTP headers.
    $this->setHttpHeaders('Status', 204);
  }

  /**
   * {@inheritdoc}
   */
  protected function updateEntity($id, $null_missing_fields = FALSE) {
    $entity_id = $this->getEntityIdByFieldId($id);
    $this->isValidEntity('update', $entity_id);

    $wrapper = entity_metadata_wrapper($this->entityType, $entity_id);

    $this->setPropertyValues($wrapper, $null_missing_fields);

    // Set the HTTP headers.
    $this->setHttpHeaders('Status', 201);

    if (!empty($wrapper->url) && $url = $wrapper->url->value()) {
      $this->setHttpHeaders('Location', $url);
    }

    return array($this->viewEntity($wrapper->getIdentifier()));
  }

  /**
   * {@inheritdoc}
   */
  public function createEntity() {
    $entity_info = $this->getEntityInfo();
    $bundle_key = $entity_info['entity keys']['bundle'];
    $values = $bundle_key ? array($bundle_key => $this->bundle) : array();

    $entity = entity_create($this->entityType, $values);

    if ($this->checkEntityAccess('create', $this->entityType, $entity) === FALSE) {
      // User does not have access to create entity.
      $params = array('@resource' => $this->getPluginKey('label'));
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
   *   Determine if properties that are missing from the request array should
   *   be treated as NULL, or should be skipped. Defaults to FALSE, which will
   *   skip, instead of setting the fields to NULL.
   *
   * @throws RestfulBadRequestException
   */
  protected function setPropertyValues(EntityMetadataWrapper $wrapper, $null_missing_fields = FALSE) {
    $request = $this->getRequest();

    static::cleanRequest($request);
    $save = FALSE;
    $original_request = $request;

    foreach ($this->getPublicFields() as $public_field_name => $info) {
      if (!empty($info['create_or_update_passthrough'])) {
        // Allow passing the value in the request.
        unset($original_request[$public_field_name]);
        continue;
      }

      if (empty($info['property'])) {
        // We may have for example an entity with no label property, but with a
        // label callback. In that case the $info['property'] won't exist, so
        // we skip this field.
        continue;
      }

      $property_name = $info['property'];

      if (!array_key_exists($public_field_name, $request)) {
        // No property to set in the request.
        if ($null_missing_fields && $this->checkPropertyAccess('edit', $public_field_name, $wrapper->{$property_name}, $wrapper)) {
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
        $field_value = $this->propertyValuesPreprocess($property_name, $request[$public_field_name], $public_field_name);
      }

      $wrapper->{$property_name}->set($field_value);

      // We check the property access only after setting the values, as the
      // access callback's response might change according to the field value.
      if (!$this->checkPropertyAccess('edit', $public_field_name, $wrapper->{$property_name}, $wrapper)) {
        throw new \RestfulBadRequestException(format_string('Property @name cannot be set.', array('@name' => $public_field_name)));
      }

      unset($original_request[$public_field_name]);
      $save = TRUE;
    }

    if (!$save) {
      // No request was sent.
      throw new \RestfulBadRequestException('No values were sent with the request');
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
    // If value is NULL, just return.
    if (!isset($value)) {
      return NULL;
    }

    // Get the field info.
    $field_info = field_info_field($property_name);

    switch ($field_info['type']) {
      case 'entityreference':
      case 'taxonomy_term_reference':
      case 'field_collection':
      case 'commerce_product_reference':
      case 'commerce_line_item_reference':
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
    if (!$value) {
      // If value is empty, return NULL, so no new entity will be created.
      return;
    }

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
   * @param mixed $value
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
    $handler = restful_get_restful_handler($resource['name'], $resource['major_version'], $resource['minor_version']);
    return $this->createOrUpdateSubResourceItems($handler, $value, $field_info);
  }

  /**
   * Create, update or return a set of already saved entities.
   *
   * @param \RestfulInterface $handler
   *   The sub resource handler.
   * @param mixed $value
   *   The value passed in the request.
   * @param array $field_info
   *   The field info array.
   *
   * @return mixed
   *   The value to set using the wrapped property.
   */
  protected function createOrUpdateSubResourceItems(\RestfulInterface $handler, $value, $field_info) {
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
  protected function createOrUpdateSubResourceItem($value, \RestfulInterface $handler) {
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
    $this->validateFields($wrapper);
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
    if (!$this->checkPropertyAccessByAccessCallbacks($op, $public_field_name, $property_wrapper, $wrapper)) {
      // Access callbacks denied access.
      return;
    }

    $account = $this->getAccount();
    // Check format access for text fields.
    if ($property_wrapper->type() == 'text_formatted' && $property_wrapper->value() && $property_wrapper->format->value()) {
      $format = (object) array('format' => $property_wrapper->format->value());
      // Only check filter access on write contexts.
      if (\RestfulBase::isWriteMethod($this->getMethod()) && !filter_access($format, $account)) {
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
      '@resource' => $this->getPluginKey('label'),
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

      if ($op == 'view' && !$this->getPath()) {
        // Just return FALSE, without an exception, for example when a list of
        // entities is requested, and we don't want to fail all the list because
        // of a single item without access.
        return FALSE;
      }

      // Entity was explicitly requested so we need to throw an exception.
      throw new RestfulForbiddenException(format_string('You do not have access to entity ID @id of resource @resource', $params));
    }

    return TRUE;
  }


  /**
   * Check access to CRUD an entity.
   *
   * @param $op
   *   The operation. Allowed values are "view", "create", "update" and
   *   "delete".
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
    $entity_info = $this->getEntityInfo();
    $id_key = $entity_info['entity keys']['id'];

    $public_fields = array(
      'id' => array(
        'wrapper_method' => 'getIdentifier',
        'wrapper_method_on_entity' => TRUE,
        'property' => $id_key,
        'discovery' => array(
          // Information about the field for human consumption.
          'info' => array(
            'label' => t('ID'),
            'description' => t('Base ID for the entity.'),
          ),
          // Describe the data.
          'data' => array(
            'type' => 'int',
            'read_only' => TRUE,
          ),
        ),
      ),
      'label' => array(
        'wrapper_method' => 'label',
        'wrapper_method_on_entity' => TRUE,
        'discovery' => array(
          // Information about the field for human consumption.
          'info' => array(
            'label' => t('Label'),
            'description' => t('The label of the resource.'),
          ),
          // Describe the data.
          'data' => array(
            'type' => 'string',
          ),
          // Information about the form element.
          'form_element' => array(
            'type' => 'textfield',
            'size' => 255,
          ),
        ),
      ),
      'self' => array(
        'callback' => array($this, 'getEntitySelf'),
      ),
    );

    if ($view_mode_info = $this->getPluginKey('view_mode')) {
      if (empty($view_mode_info['name'])) {
        throw new \RestfulServerConfigurationException('View mode not found.');
      }
      $view_mode_handler = new \RestfulEntityViewMode($this->getEntityType(), $this->getBundle());

      $public_fields += $view_mode_handler->mapFields($view_mode_info['name'], $view_mode_info['field_map']);
      return $public_fields;
    }

    if (!empty($entity_info['entity keys']['label'])) {
      $public_fields['label']['property'] = $entity_info['entity keys']['label'];
    }

    return $public_fields;
  }

  /**
   * {@inheritdoc}
   */
  protected function addDefaultValuesToPublicFields(array $public_fields = array()) {
    $public_fields = parent::addDefaultValuesToPublicFields($public_fields);
    // Set defaults values.
    foreach (array_keys($public_fields) as $key) {
      // Set default values specific for entities.
      $info = &$public_fields[$key];
      $info += array(
        'access_callbacks' => array(),
        'column' => FALSE,
        'property' => FALSE,
        'resource' => array(),
        'sub_property' => FALSE,
        'wrapper_method' => 'value',
        'wrapper_method_on_entity' => FALSE,
        'formatter' => FALSE,
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

        if ($this->getMethod() == \RestfulInterface::OPTIONS) {
          $info += $this->getFieldInfoAndFormSchema($field);
        }
      }


      foreach ($info['resource'] as &$resource) {
        // Expand array to be verbose.
        if (!is_array($resource)) {
          $resource = array('name' => $resource);
        }

        // Set default value.
        $resource += array(
          'full_view' => TRUE,
        );

        // Set the default value for the version of the referenced resource.
        if (!isset($resource['major_version']) || !isset($resource['minor_version'])) {
          list($major_version, $minor_version) = static::getResourceLastVersion($resource['name']);
          $resource['major_version'] = $major_version;
          $resource['minor_version'] = $minor_version;
        }
      }
    }

    return $public_fields;
  }

  /**
   * Get the field info, data and form element
   *
   * @param string $field
   *   The field info.
   *
   *
   * @return array
   *   Array with the 'info', 'data' and 'form_element' keys.
   */
  protected function getFieldInfoAndFormSchema($field) {
    $discovery_info = array();
    $instance_info = field_info_instance($this->getEntityType(), $field['field_name'], $this->getBundle());

    $discovery_info['info']['label'] = $instance_info['label'];
    $discovery_info['info']['description'] = $instance_info['description'];

    $discovery_info['data']['type'] = $field['type'];
    $discovery_info['data']['required'] = $instance_info['required'];

    $discovery_info['form_element']['default_value'] = isset($instance_info['default_value']) ? $instance_info['default_value'] : NULL;

    $discovery_info['form_element']['allowed_values'] = $this->getFormSchemaAllowedValues($field);

    return array('discovery' => $discovery_info);
  }

  /**
   * Get allowed values for the form schema.
   *
   * Using Field API's "Options" module to get the allowed values.
   *
   * @param array $field
   *   The field info array.
   *
   * @return mix | NULL
   *   The allowed values or NULL if none found.
   */
  protected function getFormSchemaAllowedValues($field) {
    if (!module_exists('options')) {
      return;
    }

    $entity_type = $this->getEntityType();
    $bundle = $this->getBundle();
    $instance = field_info_instance($entity_type, $field['field_name'], $bundle);

    if (!$this->formSchemaHasAllowedValues($field, $instance)) {
      // Field doesn't have allowed values.
      return;
    }

    // Use Field API's widget to get the allowed values.
    $type = str_replace('options_', '', $instance['widget']['type']);
    $multiple = $field['cardinality'] > 1 || $field['cardinality'] == FIELD_CARDINALITY_UNLIMITED;
    // Always pass TRUE for "required" and "has_value", as we don't want to get
    // the "none" option.
    $required = TRUE;
    $has_value = TRUE;
    $properties = _options_properties($type, $multiple, $required, $has_value);

    // Mock an entity.
    $values = array();
    $entity_info = $this->getEntityInfo();

    if (!empty($entity_info['entity keys']['bundle'])) {
      // Set the bundle of the entity.
      $values[$entity_info['entity keys']['bundle']] = $bundle;
    }

    $entity = entity_create($entity_type, $values);

    return _options_get_options($field, $instance, $properties, $this->getEntityType(), $entity);
  }

  /**
   * Determines if a field has allowed values.
   *
   * If Field is reference, and widget is autocomplete, so for performance
   * reasons we do not try to grab all the referenced entities.
   *
   * @param array $field
   *   The field info array.
   * @param array $instance
   *   The instance info array.
   *
   * @return bool
   *   TRUE if a field should be populated with the allowed values.
   */
  protected function formSchemaHasAllowedValues($field, $instance) {
    $field_types = array(
      'entityreference',
      'taxonomy_term_reference',
      'field_collection',
      'commerce_product_reference',
    );

    $widget_types = array(
      'taxonomy_autocomplete',
      'entityreference_autocomplete',
      'entityreference_autocomplete_tags',
      'commerce_product_reference_autocomplete',
    );

    return !in_array($field['type'], $field_types) || !in_array($instance['widget']['type'], $widget_types);
  }

  /**
   * Get the "self" url.
   *
   * @param \EntityMetadataWrapper $wrapper
   *   The wrapped entity.
   *
   * @return string
   *   The self URL.
   */
  protected function getEntitySelf(\EntityMetadataWrapper $wrapper) {
    return $this->versionedUrl($wrapper->getIdentifier());
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
   * Clear all caches corresponding to the current resource for a given entity.
   *
   * @param int $id
   *   The entity ID.
   */
  public function clearResourceRenderedCacheEntity($id) {
    // Build the cache ID.
    $version = $this->getVersion();
    $cid = 'v' . $version['major'] . '.' . $version['minor'] . '::' . $this->getResourceName() . '::uu' . $this->getAccount()->uid . '::paet:';
    $cid .= $this->getEntityType();
    $cid .= '::ei:' . $id;
    $this->cacheInvalidate($cid);
  }

  /**
   * Validates an entity's fields before they are saved.
   *
   * @param \EntityDrupalWrapper $wrapper
   *   A metadata wrapper for the entity.
   *
   * @throws \RestfulUnprocessableEntityException
   */
  protected function validateFields($wrapper) {
    try {
      field_attach_validate($wrapper->type(), $wrapper->value());
    }
    catch (\FieldValidationException $e) {
      throw new RestfulUnprocessableEntityException($e->getMessage());
    }
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
   * @throws RestfulBadRequestException
   * @throws RestfulUnprocessableEntityException
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
    $public_fields = $this->getPublicFields();
    if ((!$public_field_info = $public_fields[$public_property_name]) || empty($public_field_info['property'])) {
      throw new \RestfulBadRequestException(format_string('Cannot load an entity using the field "@name"', array(
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
      throw new RestfulUnprocessableEntityException(format_string('The entity ID @id by @name for @resource cannot be loaded.', array(
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
   * Checks if a given string represents a Field API field.
   *
   * @param string $name
   *   The name of the field/property.
   *
   * @return bool
   *   TRUE if it's a field. FALSE otherwise.
   */
  public static function propertyIsField($name) {
    $field_info = field_info_field($name);
    return !empty($field_info);
  }

}

<?php


/**
 * @file
 * Contains RestfulEntityBase.
 */

use Drupal\restful\Exception\BadRequestException;
use Drupal\restful\Exception\ForbiddenException;
use Drupal\restful\Exception\RestfulException;
use Drupal\restful\Exception\ServerConfigurationException;
use Drupal\restful\Exception\UnprocessableEntityException;

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
   * @throws BadRequestException
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
    return !empty($info['entity keys']['bundle']) ? array($this->getBundle()) : FALSE;
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
   * Get the "target_type" property from an field or property reference.
   *
   * @param \EntityMetadataWrapper $wrapper
   *   The wrapped property.
   * @param $property
   *   The public field name.
   * @return string The target type of the referenced entity.
   * The target type of the referenced entity.
   *
   * @throws UnprocessableEntityException
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

      throw new UnprocessableEntityException(format_string('Field @property is not an entity reference or taxonomy reference field.', $params));
    }
    else {
      // This is a property referencing another entity (e.g. the "uid" on the
      // node object).
      $info = $wrapper->info();
      if ($this->getEntityInfo($info['type'])) {
        return $info['type'];
      }

      throw new UnprocessableEntityException(format_string('Property @property is not defined as reference in the EntityMetadataWrapper definition.', $params));
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

    // Pipe the parent request to the sub-request.
    $piped_request = $this->getRequestForSubRequest();
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
   * @param int $entity_id
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
    $this->isValidEntity('update', $entity_id);

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
            'type' => 'texfield',
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
        throw new ServerConfigurationException('View mode not found.');
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
        if (empty($resource['major_version']) || empty($resource['minor_version'])) {
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
    );

    $widget_types = array(
      'taxonomy_autocomplete',
      'entityreference_autocomplete',
      'entityreference_autocomplete_tags',
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
    $public_fields = $this->getPublicFields();
    if ((!$public_field_info = $public_fields[$public_property_name]) || empty($public_field_info['property'])) {
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

<?php


/**
 * @file
 * Contains RestfulEntityBase.
 */

/**
 * An abstract implementation of RestfulEntityInterface.
 */
abstract class RestfulEntityBase implements RestfulEntityInterface {

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
   * The plugin definition.
   *
   * @var array $plugin
   */
  protected $plugin;

  /**
   * The public fields that are exposed to the API.
   *
   *  Array with the optional values:
   *  - "property": The entity property (e.g. "title", "nid").
   *  - "sub_property": A sub property name of a property to take from it the
   *    content. This can be used for example on a text field with filtered text
   *    input format where we would need to do $wrapper->body->value->value().
   *    Defaults to FALSE.
   *  - "wrapper_method": The wrapper's method name to perform on the field.
   *    This can be used for example to get the entity label, by setting the
   *    value to "label". Defaults to "value".
   *  - "wrapper_method_on_entity": A Boolean to indicate on what to perform
   *    the wrapper method. If TRUE the method will perform on the entity (e.g.
   *    $wrapper->label()) and FALSE on the property or sub property
   *    (e.g. $wrapper->field_reference->label()). Defaults to FALSE.
   *  - "callback": A callable callback to get a computed value. Defaults To
   *    FALSE.
   *  - "process_callback": A callable callback to perform on the returned
   *    value, or an array with the object and method. Defaults To FALSE.
   *  - "resource": This property can be assigned only to an entity reference
   *    field. Array of restful resources keyed by the target bundle. For
   *    example, if the field is referencing a node entity, with "Article" and
   *    "Page" bundles, we are able to map those bundles to their related
   *    resource. Items with bundles that were not explicitly set would be
   *    ignored.
   *    array(
   *      'article' => 'articles',
   *      'page' => 'pages',
   *    );
   *
   * @var array
   */
  protected $publicFields = array();

  /**
   * Nested array that provides information about what method to call for each
   * route pattern.
   *
   * @var array $controllers
   */
  protected $controllers = array(
    '' => array(
      // GET returns a list of entities.
      'get' => 'getList',
      // POST
      'post' => 'createEntity',
    ),
    '\d+' => array(
      'get' => 'viewEntity',
      'put' => 'putEntity',
      'patch' => 'patchEntity',
      'delete' => 'deleteEntity',
    ),
  );

  /**
   * Array keyed by the header property, and the value.
   *
   * This can be used for example to change the "Status" code of the HTTP
   * response, or to add a "Location" property.
   *
   * @var array $httpHeaders
   */
  protected $httpHeaders = array();

  /**
   * Determines the number of items that should be returned when viewing lists.
   *
   * @var int
   */
  protected $range = 50;

  /**
   * Authentication manager.
   *
   * @var \RestfulAuthenticationManager
   */
  public $authenticationManager;

  /**
   * Get the defined controllers
   *
   * @return array
   *   The defined controllers.
   */
  public function getControllers() {
    return $this->controllers;
  }

  /**
   * Set the HTTP headers.
   *
   * @param string $key
   *   The HTTP header key.
   * @param string $value
   *   The HTTP header value.
   */
  public function setHttpHeaders($key, $value) {
    $this->httpHeaders[$key] = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getHttpHeaders() {
    return $this->httpHeaders;
  }

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

  public function __construct($plugin, \RestfulAuthenticationManager $auth_manager = NULL) {
    $this->plugin = $plugin;
    $this->entityType = $plugin['entity_type'];
    $this->bundle = $plugin['bundle'];
    $this->authenticationManager = $auth_manager ? $auth_manager : new \RestfulAuthenticationManager();
  }

  /**
   * Return the resource name.
   *
   * @return string
   *   Gets the name of the resource.
   */
  public function getResourceName() {
    return $this->plugin['resource'];
  }

  /**
   * Return array keyed with the major and minor version of the resource.
   *
   * @return array
   *   Keyed array with the major and minor version as provided in the plugin
   *   definition.
   */
  public function getVersion() {
    return array(
      'major' => $this->plugin['major_version'],
      'minor' => $this->plugin['minor_version'],
    );
  }

  /**
   * Return the entity type of the resource.
   *
   * @return string
   *   Machine name of the entity type.
   */
  public function getEntityType() {
    return $this->entityType;
  }

  /**
   * Return the bundle of the resource if exists.
   *
   * @return string|bool
   *   Machine name of the bundle or FALSE if none.
   */
  public function getBundle() {
    return !empty($this->bundle) ? $this->bundle : FALSE;
  }

  /**
   * Call resource using the GET http method.
   *
   * @param string $path
   *   (optional) The path.
   * @param null $request
   *   (optional) The request.
   *
   * @return mixed
   *   The return value can depend on the controller for the get method.
   */
  public function get($path = '', $request = NULL) {
    return $this->process($path, $request, 'get');
  }

  /**
   * Call resource using the POST http method.
   *
   * @param string $path
   *   (optional) The path.
   * @param null $request
   *   (optional) The request.
   *
   * @return mixed
   *   The return value can depend on the controller for the post method.
   */
  public function post($path = '', $request = NULL) {
    return $this->process($path, $request, 'post');
  }

  /**
   * Call resource using the PUT http method.
   *
   * @param string $path
   *   (optional) The path.
   * @param null $request
   *   (optional) The request.
   *
   * @return mixed
   *   The return value can depend on the controller for the put method.
   */
  public function put($path = '', $request = NULL) {
    return $this->process($path, $request, 'put');
  }

  /**
   * Call resource using the PATCH http method.
   *
   * @param string $path
   *   (optional) The path.
   * @param null $request
   *   (optional) The request.
   * @param null $account
   *   (optional) The user object.
   */
  public function patch($path = '', $request = NULL, $account = NULL) {
    return $this->process($path, $request, $account, 'patch');
  }

  /**
   * Call resource using the DELETE http method.
   *
   * @param string $path
   *   (optional) The path.
   * @param null $request
   *   (optional) The request.
   *
   * @return mixed
   *   The return value can depend on the controller for the delete method.
   */
  public function delete($path = '', $request = NULL) {
    return $this->process($path, $request, 'delete');
  }

  /**
   * {@inheritdoc}
   */
  public function process($path = '', $request = NULL, $method = 'get') {
    $account = $this->getAccount($request);

    if (!$method_name = $this->getControllerFromPath($path, $method)) {
      throw new RestfulBadRequestException('Path does not exist');
    }

    if (!$path) {
      // If $path is empty we don't need to pass it along.
      return $this->{$method_name}($request, $account);
    }
    else {
      return $this->{$method_name}($path, $request, $account);
    }
  }

  /**
   * Return the controller from a given path.
   *
   * @param string $path
   *   The requested path.
   * @param string $http_method
   *   The requested HTTP method.
   * @return string
   *   The appropriate method to call.
   *
   * @throws RestfulBadRequestException
   * @throws RestfulGoneException
   */
  public function getControllerFromPath($path, $http_method) {
    $selected_controller = NULL;
    foreach ($this->getControllers() as $pattern => $controllers) {
      if ($pattern != $path && !($pattern && preg_match('/' . $pattern . '/', $path))) {
        continue;
      }

      if ($controllers === FALSE) {
        // Method isn't valid anymore, due to a deprecated API endpoint.
        $params = array('@path' => $path);
        throw new RestfulGoneException(format_string('The path @path endpoint is not valid.', $params));
      }

      if (!isset($controllers[$http_method])) {
        $params = array('@method' => strtolower($http_method));
        throw new RestfulBadRequestException(format_string('The http method @method is not allowed for this path.', $params));
      }

      // We found the controller, so we can break.
      $selected_controller = $controllers[$http_method];
      break;
    }

    return $selected_controller;
  }

  /**
   * Get a list of entities.
   *
   * @param array $request
   *   (optional) The request.
   * @param stdClass $account
   *   (optional) The user object.
   *
   * @return array
   *   Array of entities, as passed to RestfulEntityBase::viewEntity().
   *
   * @throws RestfulBadRequestException
   */
  public function getList($request = NULL, stdClass $account = NULL) {
    $entity_type = $this->entityType;
    $result = $this
      ->getQueryForList($request, $account)
      ->execute();


    if (empty($result[$entity_type])) {
      return array();
    }

    $ids = array_keys($result[$entity_type]);

    // Pre-load all entities.
    entity_load($entity_type, $ids);

    $return = array('list' => array());

    $this->getListAddHateoas($return, $ids, $request);

    foreach ($ids as $id) {
      $return['list'][] = $this->viewEntity($id, $request, $account);
    }

    return $return;
  }

  /**
   * Prepare a query for RestfulEntityBase::getList().
   *
   * @param null $request
   *   (optional) The request.
   * @param stdClass $account
   *   (optional) The user object.
   *
   * @return EntityFieldQuery
   *   Tee EntityFieldQuery object.
   *
   * @throws RestfulBadRequestException
   */
  public function getQueryForList($request, stdClass $account = NULL) {
    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', $this->entityType);

    if ($this->bundle) {
      $query->entityCondition('bundle', $this->bundle);
    }

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
      // Sort by default using the entity ID.
      $sorts['id'] = 'ASC';
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


    // Determine the page that should be seen. Page 1, is actually offset 0
    // in the query range.
    $page = !empty($request['page']) ? $request['page'] : 1;

    if (!ctype_digit((string)$page) || $page < 1) {
      throw new \RestfulBadRequestException('"Page" property should be numeric and equal or higher than 1.');
    }

    // We get 1 more item more than the range, in order to know if there is a
    // "next" page.
    $range = $this->getRange() + 1;
    $offset = ($page - 1) * $range;

    $query->range($offset, $range);

    return $query;
  }

  /**
   * Add HATEOAS links to list of item.
   *
   * @param $return
   *   The array that will be returned from \RestfulEntityBase::getList().
   *   Passed by reference, as this will add a "_links" property to that array.
   * @param $ids
   *   Array of entity IDs retrieved for the list. Passed by reference, so we
   *   can check if there is an extra item, thus know there is a "next" page.
   * @param $request
   *   The request array.
   */
  public function getListAddHateoas(&$return, &$ids, $request){
    $return['_links'] = array();
    $page = !empty($request['page']) ? $request['page'] : 1;

    $resource_url = $this->getPluginInfo('menu_item');

    if ($page > 1) {
      $request['page'] = $page - 1;
      $return['_links']['previous'] = $this->getUrl($request);
    }

    if (count($ids) > $this->getRange()) {
      $request['page'] = $page + 1;
      $return['_links']['next'] = $this->getUrl($request);

      // Remove the last ID, as it was just used to determine if there is a
      // "next" page.
      array_pop($ids);
    }
  }

  /**
   * View an entity.
   *
   * @param int $entity_id
   *   The entity ID.
   * @param array $request
   *   The request array.
   * @param stdClass $account
   *   The user object.
   *
   * @return array
   *   Array with the public fields populated.
   *
   * @throws Exception
   */
  public function viewEntity($entity_id, $request, stdClass $account) {
    $this->isValidEntity('view', $entity_id, $account);

    $wrapper = entity_metadata_wrapper($this->entityType, $entity_id);
    $values = array();

    $limit_fields = !empty($request['fields']) ? explode(',', $request['fields']) : array();

    foreach ($this->getPublicFields() as $public_property => $info) {
      if ($limit_fields && !in_array($public_property, $limit_fields)) {
        // Limit fields doesn't include this property.
        continue;
      }

      // Set default values.
      $info += array(
        'property' => FALSE,
        'wrapper_method' => 'value',
        'wrapper_method_on_entity' => FALSE,
        'sub_property' => FALSE,
        'process_callback' => FALSE,
        'callback' => FALSE,
        'resource' => array(),
      );

      $value = NULL;

      if ($info['callback']) {
        // Calling a callback to receive the value.
        if (!is_callable($info['callback'])) {
          $callback_name = is_array($info['callback']) ? $info['callback'][1] : $info['callback'];
          throw new Exception(format_string('Process callback function: @callback does not exists.', array('@callback' => $callback_name)));
        }

        $value = call_user_func($info['callback']);
      }
      else {
        // Exposing an entity field.
        $property = $info['property'];

        $sub_wrapper = $info['wrapper_method_on_entity'] ? $wrapper : $wrapper->{$property};

        // Check user has access to the property.
        if ($property && !$this->checkPropertyAccess($sub_wrapper, 'view')) {
          continue;
        }

        $method = $info['wrapper_method'];
        $resource = $info['resource'];

        if ($sub_wrapper instanceof EntityListWrapper) {
          // Multiple value.
          foreach ($sub_wrapper as $item_wrapper) {
            if ($info['sub_property'] && $item_wrapper->value()) {
              $item_wrapper = $item_wrapper->{$info['sub_property']};
            }

            if ($resource) {
              if ($value_from_resource = $this->getValueFromResource($item_wrapper, $property, $resource, $request, $account)) {
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
            $value = $this->getValueFromResource($sub_wrapper, $property, $resource, $request, $account);
          }
          else {
            // Wrapper method.
            $value = $sub_wrapper->{$method}();
          }
        }
      }

      if ($value && $info['process_callback']) {
        if (!is_callable($info['process_callback'])) {
          $callback_name = is_array($info['process_callback']) ? $info['process_callback'][1] : $info['process_callback'];
          throw new Exception(format_string('Process callback function: @callback does not exists.', array('@callback' => $callback_name)));
        }

        $value = call_user_func($info['process_callback'], $value);
      }

      $values[$public_property] = $value;
    }

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

    if ($field['type'] != 'entityreference') {
      throw new Exception('Property is not an entity reference field.');
    }

    return $field['settings']['target_type'];
  }

  /**
   * Get value from an entity reference field with "resource" property.
   *
   * @param EntityMetadataWrapper $wrapper
   *   The wrapped object.
   * @param string $property
   *   The property name (i.e. the field name).
   * @param array $resource
   * @param array $request
   * @param stdClass $account
   *   The user object.
   *
   * @return mixed
   *   The value if found, or NULL if bundle not defined.
   */
  protected function getValueFromResource(EntityMetadataWrapper $wrapper, $property, $resource, $request, stdClass $account) {
    $handlers = &drupal_static(__FUNCTION__, array());

    $target_type = $this->getTargetTypeFromEntityReference($property);
    if (!$entity = $wrapper->value()) {
      return;
    }

    list($id,, $bundle) = entity_extract_ids($target_type, $entity);
    if (empty($resource[$bundle])) {
      // Bundle not mapped to a resource.
      return;
    }


    if (empty($handlers[$bundle])) {
      $version = $this->getVersion();
      $handlers[$bundle] = restful_get_restful_handler($resource[$bundle], $version['major'], $version['minor']);
    }
    $bundle_handler = $handlers[$bundle];
    return $bundle_handler->viewEntity($id, $request, $account);
  }

  /**
   * Update an entity using PUT.
   *
   * Non existing properties are assumed to be exqual to NULL.
   *
   * @param $entity_id
   *   The entity ID.
   * @param $request
   *   The request array.
   * @param $account
   *   The user object.
   *
   * @return array
   *   Array with the output of the new entity, passed to
   *   RestfulEntityInterface::viewEntity().
   */
  public function putEntity($entity_id, $request, $account) {
    return $this->updateEntity($entity_id, $request, $account, TRUE);
  }

  /**
   * Update an entity using Patch.
   *
   * Non existing properties are skipped.
   *
   * @param $entity_id
   *   The entity ID.
   * @param $request
   *   The request array.
   * @param $account
   *   The user object.
   *
   * @return array
   *   Array with the output of the new entity, passed to
   *   RestfulEntityInterface::viewEntity().
   */
  public function patchEntity($entity_id, $request, $account) {
    return $this->updateEntity($entity_id, $request, $account, FALSE);
  }

  /**
   * Update an entity.
   *
   * @param $entity_id
   *   The entity ID.
   * @param $request
   *   The request array.
   * @param $account
   *   The user object.
   * @param bool $null_missing_fields
   *   Determine if properties that are missing form the request array should
   *   be treated as NULL, or should be skipped. Defaults to FALSE, which will
   *   skip missing the fields to NULL.
   *
   * @return array
   *   Array with the output of the new entity, passed to
   *   RestfulEntityInterface::viewEntity().
   */
  protected function updateEntity($entity_id, $request, $account, $null_missing_fields = FALSE) {
    $this->isValidEntity('update', $entity_id, $account);

    $wrapper = entity_metadata_wrapper($this->entityType, $entity_id);

    $this->setPropertyValues($wrapper, $request, $account, $null_missing_fields);

    // Set the HTTP headers.
    $this->setHttpHeaders('Status', 201);

    if (!empty($wrapper->url) && $url = $wrapper->url->value()); {
      $this->setHttpHeaders('Location', $url);
    }


    return $this->viewEntity($wrapper->getIdentifier(), NULL, $account);
  }


  /**
   * Create a new entity.
   *
   * @param $request
   *   The request array.
   * @param $account
   *   The user object.
   *
   * @return array
   *   Array with the output of the new entity, passed to
   *   RestfulEntityInterface::viewEntity().
   *
   * @throws RestfulForbiddenException
   */
  public function createEntity($request, $account) {
    $entity_info = entity_get_info($this->entityType);
    $bundle_key = $entity_info['entity keys']['bundle'];
    $values = array($bundle_key => $this->bundle);

    $entity = entity_create($this->entityType, $values);

    if (!entity_access('create', $this->entityType, $entity, $account)) {
      // User does not have access to create entity.
      $params = array('@resource' => $this->plugin['label']);
      throw new RestfulForbiddenException(format_string('You do not have access to create a new @resource resource.', $params));
    }

    $wrapper = entity_metadata_wrapper($this->entityType, $entity);

    $this->setPropertyValues($wrapper, $request, $account);
    return $this->viewEntity($wrapper->getIdentifier(), NULL, $account);
  }

  /**
   * Set properties of the entity based on the request, and save the entity.
   *
   * @param EntityMetadataWrapper $wrapper
   *   The wrapped entity object, passed by reference.
   * @param array $request
   *   The request array.
   * @param stdClass $account
   *   The user object.
   * @param bool $null_missing_fields
   *   Determine if properties that are missing form the request array should
   *   be treated as NULL, or should be skipped. Defaults to FALSE, which will
   *   set the fields to NULL.
   */
  protected function setPropertyValues(EntityMetadataWrapper $wrapper, $request, $account, $null_missing_fields = FALSE) {
    $save = FALSE;
    $original_request = $request;
    foreach ($this->getPublicFields() as $public_property => $info) {
      // @todo: Pass value to validators, even if it doesn't exist, so we can
      // validate required properties.

      $property_name = $info['property'];
      if (!isset($request[$public_property])) {
        // No property to set in the request.
        if ($null_missing_fields && $this->checkPropertyAccess($wrapper->{$property_name})) {
          // We need to set the value to NULL.
          $wrapper->{$property_name}->set(NULL);
        }
        continue;
      }

      if (!$this->checkPropertyAccess($wrapper->{$property_name})) {
        throw new RestfulBadRequestException(format_string('Property @name cannot be set.', array('@name' => $public_property)));
      }
      $wrapper->{$property_name}->set($request[$public_property]);
      unset($original_request[$public_property]);
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

    $wrapper->save();
  }

  /**
   * Helper method to check access on a property.
   *
   * @param EntityMetadataWrapper $property
   *   The wrapped property.
   * @param $op
   *   The operation that access should be checked for. Can be "view" or "edit".
   *   Defaults to "edit".
   *
   * @return bool
   *   TRUE if the current user has access to set the property, FALSE otherwise.
   */
  protected function checkPropertyAccess(EntityMetadataWrapper $property, $op = 'edit') {
    // @todo Hack to check format access for text fields. Should be removed once
    // this is handled properly on the Entity API level.
    if ($property->type() == 'text_formatted' && $property->value() && $property->format->value()) {
      $format = (object) array('format' => $property->format->value());
      if (!filter_access($format)) {
        return FALSE;
      }
    }

    $info = $property->info();
    if ($op == 'edit' && empty($info['setter callback'])) {
      // Property does not allow setting.
      return;
    }


    $access = $property->access($op);
    return $access === FALSE ? FALSE : TRUE;
  }

  /**
   * Determine if an entity is valid, and accessible.
   *
   * @params $op
   *   The operation to perform on the entity (view, update, delete).
   * @param $op
   * @param $entity_id
   *   The entity ID.
   * @param $account
   *   The user object.
   *
   * @return bool
   *   TRUE if entity is valid, and user can access it.
   *
   * @throws RestfulUnprocessableEntityException
   * @throws RestfulForbiddenException
   */
  protected function isValidEntity($op, $entity_id, $account) {
    $entity_type = $this->entityType;

    $params = array(
      '@id' => $entity_id,
      '@resource' => $this->plugin['label'],
    );

    if (!$entity = entity_load_single($entity_type, $entity_id)) {
      throw new RestfulUnprocessableEntityException(format_string('The specific entity ID @id for @resource does not exist.', $params));
    }

    list(,, $bundle) = entity_extract_ids($entity_type, $entity);

    $resource_bundle = $this->getBundle();
    if ($resource_bundle && $bundle != $resource_bundle) {
      throw new RestfulUnprocessableEntityException(format_string('The specified entity ID @id is not a valid @resource.', $params));
    }

    if (entity_access($op, $entity_type, $entity, $account) === FALSE) {
      // Entity was explicitly denied.
      throw new RestfulForbiddenException(format_string('You do not have access to entity ID @id of resource @resource', $params));
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getPublicFields() {
    $public_fields = $this->publicFields;

    $entity_info = entity_get_info($this->getEntityType());
    $id_key = $entity_info['entity keys']['id'];

    $public_fields += array(
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
   * Get the request array if any.
   *
   * @return array
   *
   * @todo There is no $this->request populated anywhere.
   */
  public function getRequest() {
    return isset($this->request) ? $this->request : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function access() {
    return TRUE;
  }

  /**
   * Gets information about the restful plugin.
   *
   * @param string
   *   (optional) The name of the key to return.
   *
   * @return mixed
   *   Depends on the requested value.
   */
  public function getPluginInfo($key = NULL) {
    return isset($key) ? $this->plugin[$key] : $this->plugin;
  }

  /**
   * Proxy method to get the account from the authenticationManager.
   *
   * @param $request
   *   The request.
   *
   * @return \stdClass
   *   The user object.
   */
  public function getAccount($request = NULL) {
    return $this->authenticationManager->getAccount($request);
  }

  /**
   * Proxy method to set the account from the authenticationManager.
   *
   * @param \stdClass $account
   *   The account to set.
   */
  public function setAccount(\stdClass $account) {
    $this->authenticationManager->setAccount($account);
  }

  /**
   * Helper method; Get the URL of the resource and query strings.
   *
   * By default the URL is absolute.
   *
   * @param $request
   *   The request array.
   * @param $options
   *   Array with options passed to url().
   *
   * @return string
   *   The URL address.
   */
  public function getUrl($request = NULL, $options = array()) {
    // Remove special params.
    unset($request['q'], $request['rest_call']);

    // By default set URL to be absolute.
    $options += array('absolute' => TRUE);

    return url($this->getPluginInfo('menu_item'), $options);
  }
}

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
   */
  protected $entityType;

  /**
   * The bundle.
   */
  protected $bundle;

  /**
   * The plugin definition.
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
   *    resource. Items with bundles that were not explicetly set would be
   *    ignored.
   *    array(
   *      'article' => 'articles',
   *      'page' => 'pages',
   *    );
   */
  protected $publicFields = array();

  protected $controllers = array(
    '' => array(
      // GET returns a list of entities.
      'get' => 'getList',
      // POST
      'post' => 'createEntity',
    ),
    '\d+' => array(
      'get' => 'viewEntity',
      'put' => 'updateEntity',
      'delete' => 'deleteEntity',
    ),
  );

  /**
   * Array keyed by the header property, and the value.
   *
   * This can be used for example to change the "Status" code of the HTTP
   * response, or to add a "Location" property.
   *
   * @var array
   */
  protected $httpHeaders = array();

  /**
   * Return the defined controllers.
   */
  public function getControllers () {
    return $this->controllers;
  }

  /**
   * Set the HTTP headers.
   *
   * @param string $key
   *   The HTTP header key.
   * @param string
   * The HTTP header value.
   */
  public function setHttpHeaders($key, $value) {
    $this->httpHeaders[$key] = $value;
  }

  /**
   * Return the HTTP header values.
   *
   * @return array
   */
  public function getHttpHeaders() {
    return $this->httpHeaders;
  }

  public function __construct($plugin) {
    $this->plugin = $plugin;
    $this->entityType = $plugin['entity_type'];
    $this->bundle = $plugin['bundle'];
  }

  /**
   * Return the resource name.
   *
   * @return string
   */
  public function getResourceName() {
    return $this->plugin['resource'];
  }

  /**
   * Return array keyed with the major and minor version of the resource.
   *
   * @return array
   */
  public function getVersion() {
    return array(
      'major' => $this->plugin['major_version'],
      'minor' => $this->plugin['minor_version'],
    );
  }

  /**
   * Call resource using the GET http method.
   *
   * @param string $path
   *   (optional) The path.
   * @param null $request
   *   (optional) The request.
   * @param null $account
   *   (optional) The user object.
   */
  public function get($path = '', $request = NULL, $account = NULL) {
    return $this->process($path, $request, $account, 'get');
  }

  /**
   * Call resource using the POST http method.
   *
   * @param string $path
   *   (optional) The path.
   * @param null $request
   *   (optional) The request.
   * @param null $account
   *   (optional) The user object.
   */
  public function post($path = '', $request = NULL, $account = NULL) {
    return $this->process($path, $request, $account, 'post');
  }

  /**
   * Call resource using the PUT http method.
   *
   * @param string $path
   *   (optional) The path.
   * @param null $request
   *   (optional) The request.
   * @param null $account
   *   (optional) The user object.
   */
  public function put($path = '', $request = NULL, $account = NULL) {
    return $this->process($path, $request, $account, 'put');
  }

  /**
   * Call resource using the DELETE http method.
   *
   * @param string $path
   *   (optional) The path.
   * @param null $request
   *   (optional) The request.
   * @param null $account
   *   (optional) The user object.
   */
  public function delete($path = '', $request = NULL, $account = NULL) {
    return $this->process($path, $request, $account, 'delete');
  }

  public function process($path = '', $request = NULL, $account = NULL, $method = 'get') {
    global $user;
    if (!$method_name = $this->getControllerFromPath($path, $method)) {
      throw new RestfulBadRequestException('Path does not exist');
    }

    if (empty($account)) {
      $account = user_load($user->uid);
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
   * @param $path
   * @param $http_method
   * @return null|string
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
   * @param null $request
   *   (optional) The request.
   * @param null $account
   *   (optional) The user object.
   *
   * @return
   *   Array of entities, as passed to RestfulEntityBase::viewEntity().
   *
   * @throws RestfulBadRequestException
   */
  public function getList($request, $account) {
    $entity_type = $this->entityType;
    $result = $this
      ->getQueryForList($request, $account)
      ->execute();


    if (empty($result[$entity_type])) {
      return;
    }

    $ids = array_keys($result[$entity_type]);
    // Pre-load all entities.
    entity_load($entity_type, $ids);

    $return = array('list' => array());
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
   * @param null $account
   *   (optional) The user object.
   *
   * @return EntityFieldQuery
   *   Tee EntityFieldQuery object.
   *
   * @throws RestfulBadRequestException
   */
  public function getQueryForList($request, $account) {
    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', $this->entityType);

    if ($this->bundle) {
      $query->entityCondition('bundle', $this->bundle);
    }

    $public_fields = $this->getPublicFields();

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

    // @todo: Remove hardocding, and allow pagination.
    $query->range(0, 50);

    return $query;
  }

  /**
   * View an entity.
   *
   * @param $entity_id
   *   The entity ID.
   * @param $request
   *   The request array.
   * @param $account
   *   The user object.
   *
   * @return array
   *   Array with the public fields populated.
   *
   * @throws Exception
   */
  public function viewEntity($entity_id, $request, $account) {
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
   * @param $property
   *   The property name (i.e. the field name).
   *
   * @return mixed
   *   The value if found, or NULL if bundle not defined.
   */
  protected function getValueFromResource(EntityMetadataWrapper $wrapper, $property, $resource, $request, $account) {
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

    return $handlers[$bundle]->viewEntity($id, $request, $account);
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
   *
   * @return array
   *   Array with the output of the new entity, passed to
   *   RestfulEntityInterface::entityView().
   */
  public function updateEntity($entity_id, $request, $account) {
    $this->isValidEntity('update', $entity_id, $account);
    $wrapper = entity_metadata_wrapper($this->entityType, $entity_id);

    $this->setPropertyValues($wrapper, $request, $account);

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
   *   RestfulEntityInterface::entityView().
   */
  public function createEntity($request, $account) {
    $entity_info = entity_get_info($this->entityType);
    $bundle_key = $entity_info['entity keys']['bundle'];
    $values = array($bundle_key => $this->bundle);

    $entity = entity_create($this->entityType, $values);
    $wrapper = entity_metadata_wrapper($this->entityType, $entity);

    $this->setPropertyValues($wrapper, $request, $account);
    return $this->viewEntity($wrapper->getIdentifier(), NULL, $account);
  }

  /**
   * Set properties of the entity based on the request, and save the entity.
   *
   * @param EntityMetadataWrapper $wrapper
   *   The wrapped entity object, passed by reference.
   * @param $request
   *   The request array.
   * @param $account
   *   The user object.
   */
  protected function setPropertyValues(EntityMetadataWrapper $wrapper, $request, $account) {
    foreach ($this->getPublicFields() as $public_property => $info) {
      // @todo: Pass value to validators, even if it doesn't exist, so we can
      // validate required properties.

      if (!isset($request[$public_property])) {
        // No property to set.
        continue;
      }

      $property_name = !empty($info['property']) ? $info['property'] : FALSE;
      if ($property_name && $this->checkPropertyAccess($wrapper, $property_name)) {
        $wrapper->{$property_name}->set($request[$public_property]);
      }
    }

    $wrapper->save();
  }

  /**
   * Helper method to check access on a property.
   *
   * @todo Remove this once Entity API properly handles text format access.
   *
   * @param EntityMetadataWrapper $wrapper
   *   The parent entity.
   * @param string $property_name
   *   The property name on the entity.
   * @param EntityMetadataWrapper $property
   *   The property whose access is to be checked.
   *
   * @return bool
   *   TRUE if the current user has access to set the property, FALSE otherwise.
   */
  protected function checkPropertyAccess($wrapper, $property_name) {
    $property = $wrapper->{$property_name};
    // @todo Hack to check format access for text fields. Should be removed once
    // this is handled properly on the Entity API level.
    if ($property->type() == 'text_formatted' && $property->format->value()) {
      $format = (object) array('format' => $property->format->value());
      if (!filter_access($format)) {
        return FALSE;
      }
    }

    return $property->access('edit');
  }

  /**
   * Determine if an entity is valid, and accessible.
   *
   * @params $action
   *   The operation to perform on the entity (view, update, delete).
   * @param $entity_id
   *   The entity ID.
   * @param $account
   *   The user object.
   *
   * @return
   *   TRUE if user can access entity.
   *
   * @throws RestfulUnprocessableEntityException
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

    if ($bundle != $this->plugin['bundle']) {
      throw new RestfulUnprocessableEntityException(format_string('The specified entity ID @id is not a valid @resource.', $params));
    }

    return entity_access($op, $entity_type, $entity, $account);
  }

  public function getPublicFields() {
    $public_fields = $this->publicFields;
    if (!empty($this->entityType)) {
      $public_fields += array(
        'id' => array(
          'wrapper_method' => 'getIdentifier',
          'wrapper_method_on_entity' => TRUE,
        ),
        'label' => array(
          'wrapper_method' => 'label',
          'wrapper_method_on_entity' => TRUE,
        ),
        'self' => array('property' => 'url'),
      );
    }
    return $public_fields;
  }

  public function getRequest() {
    return $this->request;
  }

  public function access() {
    return TRUE;
  }
}

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
   * Return the defined controllers.
   */
  public function getControllers () {
    return $this->controllers;
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
   *   Array of entities, as passed to RestfulEntityBase::getView().
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
   * @param $request
   * @param $account
   * @return array
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

        if ($sub_wrapper instanceof EntityListWrapper) {
          // Multiple value.
          foreach ($sub_wrapper as $item_wrapper) {

            if ($info['sub_property'] && $item_wrapper->value()) {
              $item_wrapper = $item_wrapper->{$info['sub_property']};
            }

            $value[] = $item_wrapper->{$method}();
          }
        }
        else {
          // Single value.
          if ($info['sub_property'] && $sub_wrapper->value()) {
            $sub_wrapper = $sub_wrapper->{$info['sub_property']};
          }
          $value = $sub_wrapper->{$method}();
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
   * Create a new entity.
   *
   * @param $request
   * @param $account
   * @return array
   */
  public function createEntity($request, $account) {
    $entity_info = entity_get_info($this->entityType);
    $bundle_key = $entity_info['entity keys']['bundle'];
    $values = array($bundle_key => $this->bundle);

    $entity = entity_create($this->entityType, $values);
    $wrapper = entity_metadata_wrapper($this->entityType, $entity);

    foreach ($this->getPublicFields() as $public_property => $info) {
      // @todo: Pass value to validators, even if it doesn't exist, so we can
      // validate required properties.

      if (!isset($request[$public_property])) {
        // No property to set.
        continue;
      }

      // @todo: Check access to property.
      $property_name = !empty($info['property']) ? $info['property'] : FALSE;
      if ($property_name && $this->checkPropertyAccess($wrapper, $property_name)) {
        $wrapper->{$property_name}->set($request[$public_property]);
      }
    }

    $wrapper->save();
    return $this->viewEntity($wrapper->getIdentifier(), NULL, $account);
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

    // @todo: We should use $property->access(), but this causes a notice in
    // entity_metadata_no_hook_node_access() as the $op is "upadted" instead of
    // "create".
    // return $property->access('edit');

    return TRUE;

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

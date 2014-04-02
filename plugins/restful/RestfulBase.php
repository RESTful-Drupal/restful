<?php


/**
 * @file
 * Contains RestfulBase.
 */

/**
 * An abstract implementation of RestfulInterface.
 */
abstract class RestfulBase implements RestfulInterface {

  /**
   * The plugin definition.
   */
  protected $plugin;

  protected $publicFields = array();

  protected $controllers = array(
    '' => array(
      // GET returns a list of entities.
      'get' => 'getList',
      // POST
      'post' => 'postEntity',
    ),
    '\d+' => array(
      'get' => 'getEntity',
      'put' => 'putEntity',
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
  }

  public function process($path = '', $request = NULL, $method = 'get', $account = NULL) {
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

  public function getList($request, $account) {
  }

  public function getEntity($entity_id, $request, $account) {
    $this->isValidEntity('view', $entity_id, $account);

    $wrapper = entity_metadata_wrapper($this->plugin['entity_type'], $entity_id);
    $values = array();

    $limit_fields = !empty($request['fields']) ? explode(',', $request['fields']) : array();

    foreach ($this->getPublicFields() as $public_property => $info) {

      if ($limit_fields && !in_array($public_property, $limit_fields)) {
        // Limit fields doesn't include this property.
        continue;
      }

      $info += array(
        'wrapper_method' => 'value',
      );

      if ($info['wrapper_method'] == 'value') {
        $property = $info['property'];

        if (empty($wrapper->{$property})) {
          throw new Exception(format_string('Property @property does not exist.', array('@property' => $property)));
        }

        if (!$value = $wrapper->{$property}->value()) {
          continue;
        }
      }
      else {
        $value = $wrapper->{$info['wrapper_method']}();
      }

      $values[$public_property] = $value;
    }

    return $values;
  }

  public function postEntity($entity_id, $request, $account) {

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
  public function isValidEntity($op, $entity_id, $account) {
    $entity_type = $this->plugin['entity_type'];

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
    return entity_access($op, $entity_type, $entity);
  }

  public function getPublicFields() {
    $public_fields = $this->publicFields;
    if (!empty($this->plugin['entity_type'])) {
      $public_fields += array(
        'id' => array('wrapper_method' => 'getIdentifier'),
        'label' => array('wrapper_method' => 'label'),
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

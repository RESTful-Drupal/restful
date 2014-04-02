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

    return $this->{$method_name}($path, $request, $account);
  }

  public function getControllerFromPath($path, $http_method) {
    $selected_controller = FALSE;
    foreach ($this->getControllers() as $pattern => $controllers) {
      if ($pattern != $path && ($pattern && preg_match('/' . $pattern . '/', $path))) {
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

    if (!$selected_controller) {
      return;
    }

    $method_name = strtolower($http_method) . ucfirst($selected_controller);
    return method_exists($this, $method_name) ? $method_name : NULL;
  }

  public function getList($path, $request, $account) {
  }

  public function getEntity($entity_id, $request, $account) {
    $this->isValidEntity($entity_id, $account);

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

  public function isValidEntity($entity_id, $account) {
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

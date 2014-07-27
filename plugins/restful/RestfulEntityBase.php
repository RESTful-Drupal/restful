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
   *    The callback function receive as first argument the entity
   *    EntityMetadataWrapper object.
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
      \RestfulInterface::GET => 'getList',
      // POST
      \RestfulInterface::POST => 'createEntity',
    ),
    '\d+' => array(
      \RestfulInterface::GET => 'viewEntity',
      \RestfulInterface::PUT => 'putEntity',
      \RestfulInterface::PATCH => 'patchEntity',
      \RestfulInterface::DELETE => 'deleteEntity',
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
   * Cache controller object.
   *
   * @var \DrupalCacheInterface
   */
  protected $cacheController;

  /**
   * Authentication manager.
   *
   * @var \RestfulAuthenticationManager
   */
  protected $authenticationManager;

  /**
   * Rate limit manager.
   *
   * @var \RestfulRateLimitManager
   */
  protected $rateLimitManager = NULL;

  /**
   * The HTTP method used for the request.
   *
   * @var string
   */
  protected $method = \RestfulInterface::GET;

  /**
   * Get the HTTP method used for the request.
   * @return string
   */
  public function getMethod() {
    return $this->method;
  }

  /**
   * Set the HTTP method used for the request.
   *
   * @param string $method
   *   The method name.
   */
  public function setMethod($method) {
    $this->method = $method;
  }

  /**
   * The path of the request.
   *
   * @var string
   */
  protected $path = '';

  /**
   * The request array.
   *
   * @var array
   */
  protected $request = array();

  /**
   * Return the path of the request.
   *
   * @return string
   *   String with the path.
   */
  public function getPath() {
    return $this->path;
  }

  /**
   * Set the path of the request.
   *
   * @param string $path
   */
  public function setPath($path = '') {
    $this->path = $path;
  }

  /**
   * Get the request array.
   *
   * @return array
   */
  public function getRequest() {
    return $this->request;
  }

  /**
   * Set the request array.
   *
   * @param array $request
   *   Array with the request.
   */
  public function setRequest(array $request = array()) {
    $this->request = $request;
  }

  /**
   * Helper function to remove the application generated request data.
   *
   * @param &array $request
   *   The request array to be modified.
   */
  public static function cleanRequest(&$request) {
    unset($request['__application']);
  }


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

  /**
   * Setter for $authenticationManager.
   *
   * @param \RestfulAuthenticationManager $authenticationManager
   */
  public function setAuthenticationManager($authenticationManager) {
    $this->authenticationManager = $authenticationManager;
  }

  /**
   * Getter for $authenticationManager.
   *
   * @return \RestfulAuthenticationManager
   */
  public function getAuthenticationManager() {
    return $this->authenticationManager;
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
   * Getter for $cacheController.
   *
   * @return \DrupalCacheInterface
   */
  public function getCacheController() {
    return $this->cacheController;
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
   * Setter for rateLimitManager.
   *
   * @param \RestfulRateLimitManager $rateLimitManager
   */
  public function setRateLimitManager($rateLimitManager) {
    $this->rateLimitManager = $rateLimitManager;
  }

  /**
   * Getter for rateLimitManager.

   * @return \RestfulRateLimitManager
   */
  public function getRateLimitManager() {
    return $this->rateLimitManager;
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
    $this->plugin = $plugin;
    $this->entityType = $plugin['entity_type'];
    $this->bundle = $plugin['bundle'];
    $this->authenticationManager = $auth_manager ? $auth_manager : new \RestfulAuthenticationManager();
    $this->cacheController = $cache_controller ? $cache_controller : $this->newCacheObject();
    if (!empty($plugin['rate_limit'])) {
      $this->setRateLimitManager(new \RestfulRateLimitManager($plugin['resource'], $plugin['rate_limit']));
    }
  }

  /**
   * Return the resource name.
   *
   * @return string
   *   Gets the name of the resource.
   */
  public function getResourceName() {
    return $this->getPluginInfo('resource');
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
   * Call resource using the GET http method.
   *
   * @param string $path
   *   (optional) The path.
   * @param array $request
   *   (optional) The request.
   *
   * @return mixed
   *   The return value can depend on the controller for the get method.
   */
  public function get($path = '', array $request = array()) {
    return $this->process($path, $request, \RestfulInterface::GET);
  }

  /**
   * Call resource using the POST http method.
   *
   * @param string $path
   *   (optional) The path.
   * @param array $request
   *   (optional) The request.
   *
   * @return mixed
   *   The return value can depend on the controller for the post method.
   */
  public function post($path = '', array $request = array()) {
    return $this->process($path, $request, \RestfulInterface::POST);
  }

  /**
   * Call resource using the PUT http method.
   *
   * @param string $path
   *   (optional) The path.
   * @param array $request
   *   (optional) The request.
   *
   * @return mixed
   *   The return value can depend on the controller for the put method.
   */
  public function put($path = '', array $request = array()) {
    return $this->process($path, $request, \RestfulInterface::PUT);
  }

  /**
   * Call resource using the PATCH http method.
   *
   * @param string $path
   *   (optional) The path.
   * @param array $request
   *   (optional) The request.
   * @return mixed
   *   The return value can depend on the controller for the patch method.
   */
  public function patch($path = '', array $request = array()) {
    return $this->process($path, $request, \RestfulInterface::PATCH);
  }

  /**
   * Call resource using the DELETE http method.
   *
   * @param string $path
   *   (optional) The path.
   * @param array $request
   *   (optional) The request.
   *
   * @return mixed
   *   The return value can depend on the controller for the delete method.
   */
  public function delete($path = '', array $request = array()) {
    return $this->process($path, $request, \RestfulInterface::DELETE);
  }

  /**
   * {@inheritdoc}
   */
  public function process($path = '', array $request = array(), $method = \RestfulInterface::GET, $check_rate_limit = TRUE) {
    $this->setMethod($method);
    $this->setPath($path);
    $this->setRequest($request);

    if (!$method_name = $this->getControllerFromPath()) {
      throw new RestfulBadRequestException('Path does not exist');
    }

    if ($check_rate_limit && $this->getRateLimitManager()) {
      // This will throw the appropriate exception if needed.
      $this->getRateLimitManager()->checkRateLimit($request);
    }

    return $this->{$method_name}($path);
  }

  /**
   * Return the controller from a given path.
   *
   * @return string
   *   The appropriate method to call.
   *
   * @throws RestfulBadRequestException
   * @throws RestfulGoneException
   */
  public function getControllerFromPath() {
    $path = $this->getPath();
    $method = $this->getMethod();

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

      if (!isset($controllers[$method])) {
        $params = array('@method' => strtoupper($method));
        throw new RestfulBadRequestException(format_string('The http method @method is not allowed for this path.', $params));
      }

      // We found the controller, so we can break.
      $selected_controller = $controllers[$method];
      break;
    }

    return $selected_controller;
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
    if (!empty($autocomplete_options['enable']) && isset($request['string'])) {
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

    $return = array('list' => array());

    $this->getListAddHateoas($return, $ids);

    foreach ($ids as $id) {
      $return['list'][] = $this->viewEntity($id);
    }

    return $return;
  }

  /**
   * Prepare a query for RestfulEntityBase::getList().
   *
   * @return EntityFieldQuery
   *   Tee EntityFieldQuery object.
   *
   * @throws RestfulBadRequestException
   */
  public function getQueryForList() {
    $request = $this->getRequest();

    $entity_info = entity_get_info($this->getEntityType());
    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', $this->getEntityType());

    if ($this->bundle && $entity_info['entity keys']['bundle']) {
      $query->entityCondition('bundle', $this->getBundle());
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
    $page = isset($request['page']) ? $request['page'] : 1;

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
   * Return the values of the types tags, with the ID.
   *
   * @return array
   *   Array with the found terms keys by the entity ID.
   *   ID. Otherwise, if the field allows auto-creating tags, the ID will be the
   *   term name, to indicate for client it is an unsaved term.
   *
   * @see taxonomy_autocomplete()
   */
  protected function getListForAutocomplete() {
    $request = $this->getRequest();
    if (empty($request['string'])) {
      // Empty string.
      return array();
    }

    $string = drupal_strtolower($request['string']);
    $autocomplete_options = $this->getPluginInfo('autocomplete');
    $range = $autocomplete_options['range'];

    $result = $this->getListForAutocompleteQueryResult($string, $range);

    $return = array();
    foreach ($result as $entity_id => $label) {
      $return[$entity_id] = check_plain($label);
    }

    return $return;
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
   */
  public function getListAddHateoas(&$return, &$ids){
    $request = $this->getRequest();

    $return['_links'] = array();
    $page = !empty($request['page']) ? $request['page'] : 1;

    if ($page > 1) {
      $request['page'] = $page - 1;
      $return['_links']['previous'] = $this->getUrl();
    }

    if (count($ids) > $this->getRange()) {
      $request['page'] = $page + 1;
      $return['_links']['next'] = $this->getUrl();

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
   *
   * @return array
   *   Array with the public fields populated.
   *
   * @throws Exception
   */
  public function viewEntity($entity_id) {
    $account = $this->getAccount();
    $request = $this->getRequest();

    $cached_data = $this->getRenderedEntityCache($entity_id);
    if (!empty($cached_data->data)) {
      return $cached_data->data;
    }

    $this->isValidEntity('view', $entity_id);

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

        $value = call_user_func($info['callback'], $wrapper);
      }
      else {
        // Exposing an entity field.
        $property = $info['property'];

        $sub_wrapper = $info['wrapper_method_on_entity'] ? $wrapper : $wrapper->{$property};

        // Check user has access to the property.
        if ($property && !$this->checkPropertyAccess($sub_wrapper, 'view', $wrapper)) {
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

      if ($value && $info['process_callback']) {
        if (!is_callable($info['process_callback'])) {
          $callback_name = is_array($info['process_callback']) ? $info['process_callback'][1] : $info['process_callback'];
          throw new Exception(format_string('Process callback function: @callback does not exists.', array('@callback' => $callback_name)));
        }

        $value = call_user_func($info['process_callback'], $value);
      }

      $values[$public_property] = $value;
    }

    $this->setRenderedEntityCache($values, $entity_id);
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

    return $this->viewEntity($wrapper->getIdentifier());
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
    $account = $this->getAccount();

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
    return $this->viewEntity($wrapper->getIdentifier());
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
    $account = $this->getAccount();
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
        if ($null_missing_fields && $this->checkPropertyAccess($wrapper->{$property_name}, 'edit', $wrapper)) {
          // We need to set the value to NULL.
          $wrapper->{$property_name}->set(NULL);
        }
        continue;
      }

      if (!$this->checkPropertyAccess($wrapper->{$property_name}, 'edit', $wrapper)) {
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
   * Preprocess value for "Entity reference" field types.
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
   * @return mix
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
    $resource_name = reset($public_fields[$public_field_name]['resource']);

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
      $path = $request['id'];
      // Unset the ID from the sub-request.
      unset($request['id']);
    }

    $result = $handler->process($path, $request, $method_name, FALSE);
    return $result['id'];
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
      if (!$value['property']) {
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
   * Helper method to check access on a property.
   *
   * @param EntityMetadataWrapper $property
   *   The wrapped property.
   * @param $op
   *   The operation that access should be checked for. Can be "view" or "edit".
   *   Defaults to "edit".
   * @param EntityMetadataWrapper $wrapper
   *   The wrapped entity.
   *
   * @return bool
   *   TRUE if the current user has access to set the property, FALSE otherwise.
   */
  protected function checkPropertyAccess(EntityMetadataWrapper $property, $op = 'edit', EntityMetadataWrapper $wrapper) {
    $account = $this->getAccount();
    // @todo Hack to check format access for text fields. Should be removed once
    // this is handled properly on the Entity API level.
    if ($property->type() == 'text_formatted' && $property->value() && $property->format->value()) {
      $format = (object) array('format' => $property->format->value());
      if (!filter_access($format, $account)) {
        return FALSE;
      }
    }

    $info = $property->info();
    if ($op == 'edit' && empty($info['setter callback'])) {
      // Property does not allow setting.
      return;
    }

    $access = $property->access($op, $account);
    return $access === FALSE ? FALSE : TRUE;
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
    $account = $this->getAccount();
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
   * @param string $method
   *   The HTTP method. Defaults to "get".
   *
   * @return \stdClass
   *   The user object.
   */
  public function getAccount() {
    $request = $this->getRequest();
    $method = $this->getMethod();

    $account = $this->getAuthenticationManager()->getAccount($request, $method);

    // If the limit rate is enabled for the current plugin then set the account.
    if ($this->getRateLimitManager()) {
      $this->getRateLimitManager()->setAccount($account);
    }
    return $account;
  }

  /**
   * Proxy method to set the account from the authenticationManager.
   *
   * @param \stdClass $account
   *   The account to set.
   */
  public function setAccount(\stdClass $account) {
    // If the limit rate is enabled for the current plugin then set the account.
    if ($this->getRateLimitManager()) {
      $this->getRateLimitManager()->setAccount($account);
    }
    $this->getAuthenticationManager()->setAccount($account);
  }

  /**
   * Helper method; Get the URL of the resource and query strings.
   *
   * By default the URL is absolute.
   *
   * @param $options
   *   Array with options passed to url().
   * @param $keep_query
   *   If TRUE the $request will be appended to the $options['query']. This is
   *   the typical behavior for $_GET method, however it is not for $_POST.
   *   Defaults to TRUE.
   *
   * @return string
   *   The URL address.
   */
  public function getUrl($options = array(), $keep_query = TRUE) {
    $request = $this->getRequest();
    // By default set URL to be absolute.
    $options += array(
      'absolute' => TRUE,
      'query' => array(),
    );

    if ($keep_query) {
      // Remove special params.
      unset($request['q']);
      static::cleanRequest($request);

      // Add the request as query strings.
      $options['query'] += $request;
    }

    return url($this->getPluginInfo('menu_item'), $options);
  }

  /**
   * Get the default cache object based on the plugin configuration.
   *
   * By default, this returns an instance of the DrupalDatabaseCache class.
   * Classes implementing DrupalCacheInterface can register themselves both as a
   * default implementation and for specific bins.
   *
   * @return \DrupalCacheInterface
   *   The cache object associated with the specified bin.
   *
   * @see \DrupalCacheInterface
   * @see _cache_get_object().
   */
  protected function newCacheObject() {
    // We do not use drupal_static() here because we do not want to change the
    // storage of a cache bin mid-request.
    static $cache_object;
    if (isset($cache_object)) {
      // Return cached object.
      return $cache_object;
    }

    $cache_info = $this->getPluginInfo('cache');
    $class = $cache_info['class'];
    if (empty($class)) {
      $class = variable_get('cache_class_' . $cache_info['bin']);
      if (empty($class)) {
        $class = variable_get('cache_default_class', 'DrupalDatabaseCache');
      }
    }
    $cache_object = new $class($cache_info['bin']);
    return $cache_object;
  }

  /**
   * Get a rendered entity from cache.
   *
   * @param mixed $entity_id
   *   The entity ID.
   * @param array $request
   *   The request array to match the condition how cached entity was generated.
   *
   * @return \stdClass
   *   The cache with rendered entity as returned by
   *   \RestfulEntityInterface::viewEntity().
   *
   * @see \RestfulEntityInterface::viewEntity().
   */
  protected function getRenderedEntityCache($entity_id) {
    $cache_info = $this->getPluginInfo('cache');
    if (!$cache_info['render']) {
      return;
    }

    $cid = $this->generateCacheId($entity_id);
    return $this->getCacheController()->get($cid);
  }

  /**
   * Store a rendered entity into the cache.
   *
   * @param mixed $data
   *   The data to be stored into the cache generated by
   *   \RestfulEntityInterface::viewEntity().
   * @param mixed $entity_id
   *   The entity ID.
   *
   * @return array
   *   The rendered entity as returned by \RestfulEntityInterface::viewEntity().
   *
   * @see \RestfulEntityInterface::viewEntity().
   */
  protected function setRenderedEntityCache($data, $entity_id) {
    $cache_info = $this->getPluginInfo('cache');
    if (!$cache_info['render']) {
      return;
    }

    $cid = $this->generateCacheId($entity_id);
    $this->getCacheController()->set($cid, $data, $cache_info['expire']);
  }

  /**
   * Generate a cache identifier for the request and the current entity.
   *
   * @param mixed $entity_id
   *   The entity ID.
   * @param array $request
   *   The request array to match the condition how cached entity was generated.
   *
   * @return string
   *   The cache identifier.
   */
  protected function generateCacheId($entity_id) {
    // Get the cache ID from the selected params. We will use a complex cache ID
    // for smarter invalidation. The cache id will be like:
    // v<major version>.<minor version>::et<entity type>::ei<entity id>::uu<user uid>::pa<params array>
    // The code before every bit is a 2 letter representation of the label. For
    // instance, the params array will be something like:
    // fi:id,title::re:admin
    // When the request has ?fields=id,title&restrict=admin
    $version = $this->getVersion();
    $cid = 'v' . $version['major'] . '.' . $version['minor'] . '::et' . $this->getEntityType() . '::ei' . $entity_id . '::uu' . $this->getAccount()->uid . '::pa';
    $cid_params = array();
    $request = $this->getRequest();
    foreach ($request as $param => $value) {
      // Some request parameters don't affect how the entity is rendered, this
      // means that we should skip them for the cache ID generation.
      if (in_array($param, array('page', 'sort', 'q', '__application'))) {
        continue;
      }
      // Make sure that ?fields=title,id and ?fields=id,title hit the same cache
      // identifier.
      $values = explode(',', $value);
      sort($values);
      $value = implode(',', $values);

      $cid_params[] = substr($param, 0, 2) . ':' . $value;
    }
    $cid .= implode('::', $cid_params);
    return $cid;
  }

  /**
   * Invalidates cache for a certain entity.
   *
   * @param string $cid
   *   The wildcard cache id to invalidate.
   */
  public function cacheInvalidate($cid) {
    $cache_info = $this->getPluginInfo('cache');
    if (!$cache_info['simple_invalidate']) {
      // Simple invalidation is disabled. This means it is up to the
      // implementing module to take care of the invalidation.
      return;
    }
    // If the $cid is not '*' then remove the asterisk since it can mess with
    // dynamically built wildcards.
    if ($cid != '*') {
      $pos = strpos($cid, '*');
      if ($pos !== FALSE) {
        $cid = substr($cid, 0, $pos);
      }
    }
    $this->getCacheController()->clear($cid, TRUE);
  }

}

<?php

/**
 * @file
 * Contains RestfulBase.
 */

/**
 * Class \RestfulBase
 *
 * The \RestfulDataProviderInterface is not declared as implemented on purpose
 * so the classes that extend from RestfulBase, don't eval TRUE to instanceof
 * in restful_menu_process_callback, without explicit implementation.
 */
abstract class RestfulBase extends \RestfulPluginBase implements \RestfulInterface {

  /**
   * Nested array that provides information about what method to call for each
   * route pattern.
   *
   * @var array $controllers
   */
  protected $controllers = array();

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
   * Determines the number of items that should be returned when viewing lists.
   *
   * @var int
   */
  protected $range = 50;

  /**
   * Holds additional information about the generated values. This information
   * is available to the formatters.
   *
   * @var array
   */
  protected $valueMetadata = array();

  /**
   * Determines the language of the items that should be returned.
   *
   * @var string
   */
  protected $langcode;

  /**
   * Static cache controller.
   *
   * @var \RestfulStaticCacheController
   */
  public $staticCache;

  /**
   * The public fields that are exposed to the API.
   *
   * @var array
   */
  protected $publicFields;

  /**
   * Get the cache id parameters based on the keys.
   *
   * @param $keys
   *   Keys to turn into cache id parameters.
   *
   * @return array
   *   The cache id parameters.
   */
  protected static function addCidParams($keys) {
    $cid_params = array();
    foreach ($keys as $param => $value) {
      // Some request parameters don't affect how the resource is rendered, this
      // means that we should skip them for the cache ID generation.
      if (in_array($param, array(
        '__application',
        'filter',
        'loadByFieldName',
        'page',
        'q',
        'range',
        'sort',
      ))) {
        continue;
      }
      // Make sure that ?fields=title,id and ?fields=id,title hit the same cache
      // identifier.
      $values = explode(',', $value);
      sort($values);
      $value = implode(',', $values);

      $cid_params[] = substr($param, 0, 2) . ':' . $value;
    }
    return $cid_params;
  }

  /**
   * Get value metadata.
   *
   * @param mixed $id
   *   The resource item id.
   * @param string $public_field_name
   *   The public field name as in the output.
   *
   * @return array
   *   An associative array containing extra metadata about the requested value.
   */
  public function getValueMetadata($id, $public_field_name) {
    return isset($this->valueMetadata[$id][$public_field_name]) ? $this->valueMetadata[$id][$public_field_name] : NULL;
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
    $this->path = implode(',', array_unique(array_filter(explode(',', $path))));
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
   * Gets a request array with the data that should be piped to sub requests.
   *
   * @return array
   *   The request array to be piped.
   */
  protected function getRequestForSubRequest() {
    $piped_request = array();

    foreach ($this->getRequest() as $key => $value) {
      if (in_array($key, array(
          'filter',
          'page',
          'q',
          'range',
          'sort',
          'fields',
        ))) {
        continue;
      }

      $piped_request[$key] = $value;
    }

    return $piped_request;
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
   * Returns the default controllers for the entity.
   *
   * @return array
   *   Nested array that provides information about what method to call for each
   *   route pattern.
   */
  public static function controllersInfo() {
    // Provide sensible defaults for the HTTP methods. These methods (index,
    // create, view, update and delete) are not implemented in this layer but
    // they are guaranteed to exist because we are enforcing that all restful
    // resources are an instance of \RestfulDataProviderInterface.
    return array(
      '' => array(
        // GET returns a list of entities.
        \RestfulInterface::GET => 'index',
        \RestfulInterface::HEAD => 'index',
        // POST
        \RestfulInterface::POST => 'create',
      ),
      // We don't know what the ID looks like, assume that everything is the ID.
      '^.*$' => array(
        \RestfulInterface::GET => 'view',
        \RestfulInterface::HEAD => 'view',
        \RestfulInterface::PUT => 'replace',
        \RestfulInterface::PATCH => 'update',
        \RestfulInterface::DELETE => 'remove',
      ),
    );
  }

  /**
   * Get the defined controllers
   *
   * @return array
   *   The defined controllers.
   */
  public function getControllers() {
    if (!empty($this->controllers)) {
      return $this->controllers;
    }
    $this->controllers = static::controllersInfo();
    return $this->controllers;
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function addHttpHeaders($key, $value) {
    $headers = $this->getHttpHeaders();
    // Add a value to the (potentially) existing header.
    $values = array();
    if (!empty($headers[$key])) {
      $values[] = $headers[$key];
    }
    $values[] = $value;
    $header = implode(', ', $values);
    $this->setHttpHeaders($key, $header);
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
   * Getter for $cacheController.
   *
   * @return \DrupalCacheInterface
   */
  public function getCacheController() {
    return $this->cacheController;
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
   *
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
  public function __construct(array $plugin, \RestfulAuthenticationManager $auth_manager = NULL, \DrupalCacheInterface $cache_controller = NULL, $langcode = NULL) {
    parent::__construct($plugin);
    $this->authenticationManager = $auth_manager ? $auth_manager : new \RestfulAuthenticationManager();
    $this->cacheController = $cache_controller ? $cache_controller : $this->newCacheObject();
    if ($rate_limit = $this->getPluginKey('rate_limit')) {
      $this->setRateLimitManager(new \RestfulRateLimitManager($this->getPluginKey('resource'), $rate_limit));
    }
    $this->staticCache = new \RestfulStaticCacheController();
    if (is_null($langcode)) {
      global $language;
      $this->langcode = $language->language;
    }
    else {
      $this->langcode = $langcode;
    }
  }

  /**
   * Process plugin options by validation keys exists, and set default values.
   *
   * @param array $required_keys
   *   Array of required keys.
   * @param array $default_values
   *   Array of default values to populate in the
   *   $plugin['data_provider_options'].
   *
   * @return array
   *   Array with data provider options populated with default values.
   *
   * @throws \RestfulServiceUnavailable
   */
  protected function processDataProviderOptions($required_keys = array(), $default_values = array()) {
    $options = $this->getPluginKey('data_provider_options');
    $params = array('@class' => get_class($this));
    // Check required keys exist.
    foreach ($required_keys as $key) {
      if (empty($options[$key])) {
        $params['@key'] = $key;
        throw new \RestfulServiceUnavailable(format_string('@class is missing "@key" property in the "data_provider_options" key of the $plugin', $params));
      }
    }

    // Add default values.
    $options += $default_values;
    $this->setPluginKey('data_provider_options', $options);

    return $options;
  }

  /**
   * Determines if the HTTP method represents a write operation.
   *
   * @param string $method
   *   The method name.
   * @param boolean $strict
   *   TRUE if the comparisons are case sensitive.
   *
   * @return boolean
   *   TRUE if it is a write operation. FALSE otherwise.
   */
  public static function isWriteMethod($method, $strict = TRUE) {
    $method = $strict ? $method : strtoupper($method);
    return in_array($method, array(
      \RestfulInterface::PUT,
      \RestfulInterface::POST,
      \RestfulInterface::PATCH,
      \RestfulInterface::DELETE,
    ));
  }

  /**
   * Determines if the HTTP method represents a read operation.
   *
   * @param string $method
   *   The method name.
   * @param boolean $strict
   *   TRUE if the comparisons are case sensitive.
   *
   * @return boolean
   *   TRUE if it is a read operation. FALSE otherwise.
   */
  public static function isReadMethod($method, $strict = TRUE) {
    $method = $strict ? $method : strtoupper($method);
    return in_array($method, array(
      \RestfulInterface::GET,
      \RestfulInterface::HEAD,
      \RestfulInterface::OPTIONS,
      \RestfulInterface::TRACE,
      \RestfulInterface::CONNECT,
    ));
  }

  /**
   * Determines if the HTTP method is one of the known methods.
   *
   * @param string $method
   *   The method name.
   * @param boolean $strict
   *   TRUE if the comparisons are case sensitive.
   *
   * @return boolean
   *   TRUE if it is a known method. FALSE otherwise.
   */
  public static function isValidMethod($method, $strict = TRUE) {
    $method = $strict ? $method : strtolower($method);
    return static::isReadMethod($method, $strict) || static::isWriteMethod($method, $strict);
  }

  /**
   * Call the output format on the given data.
   *
   * @param array $data
   *   The array of data to format.
   *
   * @return string
   *   The formatted output.
   */
  public function format(array $data) {
    return $this->formatter()->format($data);
  }

  /**
   * Get the formatter handler for the current restful formatter.
   *
   * @return \RestfulFormatterInterface
   *   The formatter handler.
   */
  protected function formatter() {
    return \RestfulManager::outputFormat($this);
  }

  /**
   * Execute a user callback.
   *
   * @param mixed $callback
   *   There are 3 ways to define a callback:
   *     - String with a function name. Ex: 'drupal_map_assoc'.
   *     - An array containing an object and a method name of that object.
   *       Ex: array($this, 'format').
   *     - An array containing any of the methods before and an array of
   *       parameters to pass to the callback.
   *       Ex: array(array($this, 'processing'), array('param1', 2))
   * @param array $params
   *   Array of additional parameters to pass in.
   *
   * @return mixed
   *   The return value of the callback.
   *
   * @throws \RestfulException
   */
  public static function executeCallback($callback, array $params = array()) {
    if (!is_callable($callback)) {
      if (is_array($callback) && count($callback) == 2 && is_array($callback[1])) {
        // This code deals with the third scenario in the docblock. Get the
        // callback and the parameters from the array, merge the parameters with
        // the existing ones and call recursively to reuse the logic for the
        // other cases.
        return static::executeCallback($callback[0], array_merge($params, $callback[1]));
      }
      $callback_name = is_array($callback) ? $callback[1] : $callback;
      throw new \RestfulException(format_string('Callback function: @callback does not exists.', array('@callback' => $callback_name)));
    }

    return call_user_func_array($callback, $params);
  }

  /**
   * Return the resource name.
   *
   * @return string
   *   Gets the name of the resource.
   */
  public function getResourceName() {
    return $this->getPluginKey('resource');
  }

  /**
   * Return array keyed with the major and minor version of the resource.
   *
   * @return array
   *   Keyed array with the major and minor version as provided in the plugin
   *   definition.
   */
  public function getVersion() {
    $version = $this->staticCache->get(__CLASS__ . '::' . __FUNCTION__);
    if (isset($version)) {
      return $version;
    }
    $version = array(
      'major' => $this->getPluginKey('major_version'),
      'minor' => $this->getPluginKey('minor_version'),
    );
    $this->staticCache->set(__CLASS__ . '::' . __FUNCTION__, $version);
    return $version;
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
  public function head($path = '', array $request = array()) {
    $this->process($path, $request, \RestfulInterface::HEAD);
    return array();
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
   * Call resource using the OPTIONS http method.
   *
   * This is an special method since it does not return anything in the body, it
   * only provides information about the selected endpoint. The information is
   * provided via HTTP headers.
   *
   * @param string $path
   *   (optional) The path.
   * @param array $request
   *   (optional) The request.
   *
   * @return array
   *   Information about the fields in the current resource.
   */
  public function options($path = '', array $request = array()) {
    $this->setMethod(\RestfulInterface::OPTIONS);
    $this->setPath($path);
    $this->setRequest($request);
    // A list of discoverable methods.
    $allowed_methods = array();
    foreach ($this->getControllers() as $pattern => $controllers) {
      // Find the controllers for the provided path.
      if ($pattern != $path && !($pattern && preg_match('/' . $pattern . '/', $path))) {
        continue;
      }
      $allowed_methods = array_keys($controllers);
      // We have found the controllers for this path.
      break;
    }
    if (!empty($allowed_methods)) {
      $this->setHttpHeaders('Access-Control-Allow-Methods', implode(',', $allowed_methods));
    }

    // Make your formatters discoverable.
    $formatter_names = $this->formatterNames();
    // Loop through all the formatters and add the Content-Type header to the
    // array.
    $accepted_formats = array();
    foreach ($formatter_names as $formatter_name) {
      $formatter = restful_get_formatter_handler($formatter_name, $this);
      $accepted_formats[] = $formatter->getContentTypeHeader();
    }
    if (!empty($accepted_formats)) {
      $this->setHttpHeaders('Accept', implode(',', $accepted_formats));
    }

    $output = array();
    // Default options for the discovery information.
    $discovery_defaults = array(
      'info' => array(
        'label' => '',
        'description' => '',
      ),
      // Describe the data.
      'data' => array(
        'type' => NULL,
        'read_only' => FALSE,
        'cardinality' => 1,
        'required' => FALSE,
      ),
      // Information about the form element.
      'form_element' => array(
        'type' => NULL,
        'default_value' => '',
        'placeholder' => '',
        'size' => NULL,
        'allowed_values' => NULL,
      ),
    );

    foreach ($this->getPublicFields() as $public_field => $field_info) {
      if (empty($field_info['discovery'])) {
        continue;
      }
      $output[$public_field] = drupal_array_merge_deep($discovery_defaults, $field_info['discovery']);
    }
    return $output;

  }

  /**
   * {@inheritdoc}
   */
  public function process($path = '', array $request = array(), $method = \RestfulInterface::GET, $check_rate_limit = TRUE) {
    $this->setMethod($method);
    $this->setPath($path);
    $this->setRequest($request);

    // Clear all static caches from previous requests.
    $this->staticCache->clearAll();

    // Override the range with the value in the URL.
    $this->overrideRange();

    $version = $this->getVersion();
    $this->setHttpHeaders('X-API-Version', 'v' . $version['major']  . '.' . $version['minor']);

    if (!$method_name = $this->getControllerFromPath()) {
      throw new RestfulBadRequestException('Path does not exist');
    }

    if ($check_rate_limit && $this->getRateLimitManager()) {
      // This will throw the appropriate exception if needed.
      $this->getRateLimitManager()->checkRateLimit($request);
    }

    $return = $this->{$method_name}($path);

    if (empty($request['__application']['rest_call'])) {
      // Switch back to the original user.
      $this->getAuthenticationManager()->switchUserBack();
    }


    return $return;

  }

  /**
   * Return the controller from a given path.
   *
   * @throws RestfulBadRequestException
   * @throws RestfulException
   * @throws RestfulForbiddenException
   * @throws RestfulGoneException
   *
   * @return string
   *   The appropriate method to call.
   *
   */
  public function getControllerFromPath() {
    $path = $this->getPath();
    $method = $this->getMethod();

    $selected_controller = NULL;
    foreach ($this->getControllers() as $pattern => $controllers) {
      // Find the controllers for the provided path.
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
      if (is_array($selected_controller)) {
        // If there is a custom access method for this endpoint check it.
        if (!empty($selected_controller['access callback']) && !static::executeCallback(array($this, $selected_controller['access callback']), array($path))) {
          throw new \RestfulForbiddenException(format_string('You do not have access to this endpoint: @method - @path', array(
            '@method' => $method,
            '@path' => $path,
          )));
        }
        $selected_controller = $selected_controller['callback'];
      }
      break;
    }

    return $selected_controller;
  }

  /**
   * Helper method to know if the current request is for a list.
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
   * Adds query tags and metadata to the EntityFieldQuery.
   *
   * @param \EntityFieldQuery|\SelectQuery $query
   *   The query to enhance.
   */
  protected function addExtraInfoToQuery($query) {
    // Add a generic tags to the query.
    $query->addTag('restful');
    $query->addMetaData('account', $this->getAccount());
  }

  /**
   * Parses the request to get the sorting options.
   *
   * @return array
   *   With the different sorting options.
   *
   * @throws \RestfulBadRequestException
   */
  protected function parseRequestForListSort() {
    $request = $this->getRequest();
    $public_fields = $this->getPublicFields();

    if (empty($request['sort'])) {
      return array();
    }
    $url_params = $this->getPluginKey('url_params');
    if (!$url_params['sort']) {
      throw new \RestfulBadRequestException('Sort parameters have been disabled in server configuration.');
    }

    $sorts = array();
    foreach (explode(',', $request['sort']) as $sort) {
      $direction = $sort[0] == '-' ? 'DESC' : 'ASC';
      $sort = str_replace('-', '', $sort);
      // Check the sort is on a legal key.
      if (empty($public_fields[$sort])) {
        throw new RestfulBadRequestException(format_string('The sort @sort is not allowed for this path.', array('@sort' => $sort)));
      }

      $sorts[$sort] = $direction;
    }
    return $sorts;
  }

  /**
   * Parses the request object to get the pagination options.
   *
   * @return array
   *   A numeric array with the offset and length options.
   *
   * @throws \RestfulBadRequestException
   */
  protected function parseRequestForListPagination() {
    $request = $this->getRequest();
    $page = isset($request['page']) ? $request['page'] : 1;

    if (!ctype_digit((string) $page) || $page < 1) {
      throw new \RestfulBadRequestException('"Page" property should be numeric and equal or higher than 1.');
    }

    $range = $this->getRange();
    $offset = ($page - 1) * $range;
    return array($offset, $range);
  }

  /**
   * Filter the query for list.
   *
   * @throws \RestfulBadRequestException
   *
   * @returns array
   *   An array of filters to apply.
   *
   * @see \RestfulEntityBase::getQueryForList
   */
  protected function parseRequestForListFilter() {
    if (!$this->isListRequest()) {
      // Not a list request, so we don't need to filter.
      // We explicitly check this, as this function might be called from a
      // formatter plugin, after RESTful's error handling has finished, and an
      // invalid key might be passed.
      return array();
    }
    $request = $this->getRequest();
    if (empty($request['filter'])) {
      // No filtering is needed.
      return array();
    }
    $url_params = $this->getPluginKey('url_params');
    if (!$url_params['filter']) {
      throw new \RestfulBadRequestException('Filter parameters have been disabled in server configuration.');
    }

    $filters = array();
    $public_fields = $this->getPublicFields();

    foreach ($request['filter'] as $public_field => $value) {
      if (empty($public_fields[$public_field])) {
        throw new RestfulBadRequestException(format_string('The filter @filter is not allowed for this path.', array('@filter' => $public_field)));
      }

      // Filtering can be achieved in different ways:
      //   1. filter[foo]=bar
      //   2. filter[foo][0]=bar&filter[foo][1]=baz
      //   3. filter[foo][value]=bar
      //   4. filter[foo][value][0]=bar&filter[foo][value][1]=baz
      if (!is_array($value)) {
        // Request uses the shorthand form for filter. For example
        // filter[foo]=bar would be converted to filter[foo][value] = bar.
        $value = array('value' => $value);
      }
      if (!is_array($value['value'])) {
        $value['value'] = array($value['value']);
      }
      // Add the property
      $value['public_field'] = $public_field;

      // Set default operator count as `1` when value is empty.
      // When PHP < 5.6 and the passed value is an empty array we get:
      // `array_fill(): Number of elements must be positive` exception.
      $default_operator_count = count($value['value']) ? count($value['value']) : 1;
      // Set default operator.
      $value += array('operator' => array_fill(0, $default_operator_count, '='));
      if (!is_array($value['operator'])) {
        $value['operator'] = array($value['operator']);
      }

      // Make sure that we have the same amount of operators than values.
      if (!in_array(strtoupper($value['operator'][0]), array('IN', 'NOT IN', 'BETWEEN')) && count($value['value']) != count($value['operator'])) {
        throw new RestfulBadRequestException('The number of operators and values has to be the same.');
      }

      $value += array('conjunction' => 'AND');

      // Clean the operator in case it came from the URL.
      // e.g. filter[minor_version][operator]=">="
      $value['operator'] = str_replace(array('"', "'"), '', $value['operator']);

      static::isValidOperatorsForFilter($value['operator']);
      static::isValidConjunctionForFilter($value['conjunction']);

      $filters[] = $value;
    }

    return $filters;
  }

  /**
   * Check if an operator is valid for filtering.
   *
   * @param array $operators
   *   The array of operators.
   *
   * @throws RestfulBadRequestException
   */
  protected static function isValidOperatorsForFilter(array $operators) {
    $allowed_operators = array(
      '=',
      '>',
      '<',
      '>=',
      '<=',
      '<>',
      '!=',
      'BETWEEN',
      'CONTAINS',
      'IN',
      'NOT IN',
      'STARTS_WITH',
    );

    foreach ($operators as $operator) {
      if (!in_array($operator, $allowed_operators)) {
        throw new \RestfulBadRequestException(format_string('Operator "@operator" is not allowed for filtering on this resource. Allowed operators are: !allowed', array(
          '@operator' => $operator,
          '!allowed' => implode(', ', $allowed_operators),
        )));
      }
    }
  }

  /**
   * Check if a conjunction is valid for filtering.
   *
   * @param string $conjunction
   *   The operator.
   *
   * @throws RestfulBadRequestException
   */
  protected static function isValidConjunctionForFilter($conjunction) {
    $allowed_conjunctions = array(
      'AND',
      'OR',
      'XOR',
    );

    if (!in_array(strtoupper($conjunction), $allowed_conjunctions)) {
      throw new \RestfulBadRequestException(format_string('Conjunction "@conjunction" is not allowed for filtering on this resource. Allowed conjunctions are: !allowed', array(
        '@conjunction' => $conjunction,
        '!allowed' => implode(', ', $allowed_conjunctions),
      )));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPublicFields() {
    if ($this->publicFields) {
      // Return early.
      return $this->publicFields;
    }

    $public_fields = $this->publicFieldsInfo();

    // Cache the processed fields.
    $this->setPublicFields($public_fields);

    return $this->publicFields;
  }

  /**
   * Set the public fields.
   *
   * @param array $public_fields
   *   The unprocessed public fields array.
   */
  public function setPublicFields(array $public_fields = array()) {
    $this->publicFields = $this->addDefaultValuesToPublicFields($public_fields);
  }

  /**
   * Add default values to the public fields array.
   *
   * @param array $public_fields
   *   The unprocessed public fields array.
   *
   * @return array
   *   The processed public fields array.
   */
  protected function addDefaultValuesToPublicFields(array $public_fields = array()) {
    // Set defaults values.
    foreach (array_keys($public_fields) as $key) {
      // Set default values.
      $info = &$public_fields[$key];
      $info += array(
        'process_callbacks' => array(),
        'callback' => FALSE,
        'create_or_update_passthrough' => FALSE,
      );
    }

    return $public_fields;
  }

  /**
   * {@inheritdoc}
   */
  public function access() {
    return $this->accessByAllowOrigin();
  }

  /**
   * Proxy method to get the account from the authenticationManager.
   *
   * @param boolean $cache
   *   Boolean indicating if the resolved user should be cached for next calls.
   *
   * @return \stdClass
   *   The user object.
   */
  public function getAccount($cache = TRUE) {
    // The request.
    $request = $this->getRequest();
    // The HTTP method. Defaults to "get".
    $method = $this->getMethod();

    $account = $this->getAuthenticationManager()->getAccount($request, $method, $cache);

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
   * @param $request
   *   The request array.
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
  public function getUrl($request = NULL, $options = array(), $keep_query = TRUE) {
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

    return $this->versionedUrl('', $options);
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

    $cache_info = $this->getPluginKey('render_cache');
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
   * Get an entry from the rendered cache.
   *
   * @param array $context
   *   An associative array with additional information to build the cache ID.
   *
   * @return \stdClass
   *   The cache with rendered entity as returned by
   *   \RestfulEntityInterface::viewEntity().
   *
   * @see \RestfulEntityInterface::viewEntity().
   */
  protected function getRenderedCache(array $context = array()) {
    $cache_info = $this->getPluginKey('render_cache');
    if (!$cache_info['render']) {
      return;
    }

    $cid = $this->generateCacheId($context);
    return $this->getCacheController()->get($cid);
  }

  /**
   * Store an entry in the rendered cache.
   *
   * @param mixed $data
   *   The data to be stored into the cache generated by
   *   \RestfulEntityInterface::viewEntity().
   * @param array $context
   *   An associative array with additional information to build the cache ID.
   *
   * @return array
   *   The rendered entity as returned by \RestfulEntityInterface::viewEntity().
   *
   * @see \RestfulEntityInterface::viewEntity().
   */
  protected function setRenderedCache($data, array $context = array()) {
    $cache_info = $this->getPluginKey('render_cache');
    if (!$cache_info['render']) {
      return;
    }

    $cid = $this->generateCacheId($context);
    $this->getCacheController()->set($cid, $data, $cache_info['expire']);
  }

  /**
   * Clear an entry from the rendered cache.
   *
   * @param array $context
   *   An associative array with additional information to build the cache ID.
   *
   * @see \RestfulEntityInterface::viewEntity().
   */
  protected function clearRenderedCache(array $context = array()) {
    $cache_info = $this->getPluginKey('render_cache');
    if (!$cache_info['render']) {
      return;
    }

    $cid = $this->generateCacheId($context);
    return $this->getCacheController()->clear($cid);
  }

  /**
   * Clear all caches corresponding to the current resource.
   */
  public function clearResourceRenderedCache() {
    // Build the cache ID.
    $version = $this->getVersion();
    $cid = 'v' . $version['major'] . '.' . $version['minor'] . '::' . $this->getResourceName();
    $this->cacheInvalidate($cid);
  }

  /**
   * Generate a cache identifier for the request and the current context.
   *
   * This cache ID may be used by all RestfulDataProviderInterface.
   *
   * @param array $context
   *   An associative array with additional information to build the cache ID.
   *
   * @return string
   *   The cache identifier.
   */
  protected function generateCacheId(array $context = array()) {
    // For performance reasons create the request part and cache it, then add
    // the context part.
    $base_cid = $this->staticCache->get(__CLASS__ . '::' . __FUNCTION__);
    if (!isset($base_cid)) {
      // Get the cache ID from the selected params. We will use a complex cache
      // ID for smarter invalidation. The cache id will be like:
      // v<major version>.<minor version>::uu<user uid>::pa<params array>
      // The code before every bit is a 2 letter representation of the label.
      // For instance, the params array will be something like:
      // fi:id,title::re:admin
      // When the request has ?fields=id,title&restrict=admin
      $version = $this->getVersion();
      $account = $this->getAccount();
      $cache_info = $this->getPluginKey('render_cache');
      if ($cache_info['granularity'] == DRUPAL_CACHE_PER_USER) {
        $account_cid = '::uu' . $account->uid;
      }
      elseif ($cache_info['granularity'] == DRUPAL_CACHE_PER_ROLE) {
        // Instead of encoding the user ID in the cache ID add the role ids.
        $account_cid = '::ur' . implode(',', array_keys($account->roles));
      }
      else {
        throw new \RestfulNotImplementedException(format_string('The selected cache granularity (@granularity) is not supported.', array(
          '@granularity' => $cache_info['granularity'],
        )));
      }
      $base_cid = 'v' . $version['major'] . '.' . $version['minor'] . '::' . $this->getResourceName() . $account_cid . '::pa';
      $this->staticCache->set(__CLASS__ . '::' . __FUNCTION__, $base_cid);
    }
    // Now add the context part to the cid
    $cid_params = static::addCidParams($context);
    if ($this->isReadMethod($this->getMethod())) {
      // We don't want to split the cache with the body data on write requests.
      $request = $this->getRequest();
      static::cleanRequest($request);
      $cid_params = array_merge($cid_params, static::addCidParams($request));
    }

    return $base_cid . implode('::', $cid_params);
  }

  /**
   * Invalidates cache for a certain entity.
   *
   * @param string $cid
   *   The wildcard cache id to invalidate. Do not add * for the wildcard.
   */
  public function cacheInvalidate($cid) {
    $cache_info = $this->getPluginKey('render_cache');
    if (!$cache_info['render'] || !$cache_info['simple_invalidate']) {
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

  /**
   * Returns the names of the available formatter plugins.
   *
   * @return array
   *   Array of formatter names.
   */
  public function formatterNames() {
    $plugin_info = $this->getPlugin();
    if (!empty($plugin_info['formatter'])) {
      // If there is formatter info in the plugin definition, return that.
      return array($plugin_info['formatter']);
    }
    // If there is no formatter info in the plugin definition, return a list
    // of all the formatters available.
    $formatter_names = array();
    foreach (restful_get_formatter_plugins() as $formatter_info) {
      $formatter_names[] = $formatter_info['name'];
    }
    return $formatter_names;
  }

  /**
   * Checks access based on the referer header and the allow_origin setting.
   *
   * @return bool
   *   TRUE if the access is granted. FALSE otherwise.
   */
  protected function accessByAllowOrigin() {
    // Check the referrer header and return false if it does not match the
    // Access-Control-Allow-Origin
    $referer = \RestfulManager::getRequestHttpHeader('Referer', '');
    // If there is no allow_origin assume that it is allowed. Also, if there is
    // no referer then grant access since the request probably was not
    // originated from a browser.
    $origin = $this->getPluginKey('allow_origin');
    if (empty($origin) || $origin == '*' || !$referer) {
      return TRUE;
    }
    return strpos($referer, $origin) === 0;
  }

  /**
   * Return the last version for a given resource.
   *
   * @param string $resource_name
   *   The name of the resource.
   * @param int $major_version
   *   Get the last version for this major version. If NULL the last major
   *   version for the resource will be used.
   *
   * @return array
   *   Array containing the major_version and minor_version.
   */
  public static function getResourceLastVersion($resource_name, $major_version = NULL) {
    $resources = array();
    // Get all the resources corresponding to the resource name.
    foreach (restful_get_restful_plugins() as $resource) {
      if ($resource['resource'] != $resource_name || (isset($major_version) && $resource['major_version'] != $major_version)) {
        continue;
      }
      $resources[$resource['major_version']][$resource['minor_version']] = $resource;
    }
    // Sort based on the major version.
    ksort($resources, SORT_NUMERIC);
    // Get a list of resources for the latest major version.
    $resources = end($resources);
    if (empty($resources)) {
      return;
    }
    // Sort based on the minor version.
    ksort($resources, SORT_NUMERIC);
    // Get the latest resource for the minor version.
    $resource = end($resources);
    return array($resource['major_version'], $resource['minor_version']);
  }

  /**
   * Gets the major and minor version for the current request.
   *
   * @param string $path
   *   The router path.
   *
   * @return array
   *   The array with the version.
   */
  public static function getVersionFromRequest($path = NULL) {
    $version = &drupal_static(__CLASS__ . '::' . __FUNCTION__);
    if (isset($version)) {
      return $version;
    }
    list($resource_name, $version) = static::getPageArguments($path);
    if (preg_match('/^v\d+(\.\d+)?$/', $version)) {
      $version = static::parseVersionString($version, $resource_name);
      return $version;
    }
    // If there is no version in the URL check the header.
    if ($api_version = \RestfulManager::getRequestHttpHeader('X-API-Version')) {
      $version =  static::parseVersionString($api_version, $resource_name);
      return $version;
    }

    // If there is no version negotiation information return the latest version.
    $version = static::getResourceLastVersion($resource_name);
    return $version;
  }

  /**
   * Gets a resource URL based on the current version.
   *
   * @param string $path
   *   The path for the resource
   * @param array $options
   *   Array of options as in url().
   * @param boolean $version_string
   *   TRUE to add the version string to the URL. FALSE otherwise.
   *
   * @return string
   *   The fully qualified URL.
   *
   * @see url().
   */
  public function versionedUrl($path = '', $options = array(), $version_string = TRUE) {
    // Make the URL absolute by default.
    $options += array('absolute' => TRUE);
    $plugin = $this->getPlugin();
    if (!empty($plugin['menu_item'])) {
      $url = $plugin['menu_item'] . '/' . $path;
      return url(rtrim($url, '/'), $options);
    }

    $base_path = variable_get('restful_hook_menu_base_path', 'api');
    $url = $base_path;
    if ($version_string) {
      $url .= '/v' . $plugin['major_version'] . '.' . $plugin['minor_version'];
    }
    $url .= '/' . $plugin['resource'] . '/' . $path;
    return url(rtrim($url, '/'), $options);
  }

  /**
   * Get the non translated menu item.
   *
   * @param string $path
   *   The path to match the router item. Leave it empty to use the current one.
   *
   * @return array
   *   The page arguments.
   *
   * @see menu_get_item().
   */
  public static function getMenuItem($path = NULL) {
    $router_items = &drupal_static(__CLASS__ . '::' . __FUNCTION__);
    if (!isset($path)) {
      $path = $_GET['q'];
    }
    if (!isset($router_items[$path])) {
      $original_map = arg(NULL, $path);

      $parts = array_slice($original_map, 0, MENU_MAX_PARTS);
      $ancestors = menu_get_ancestors($parts);
      $router_item = db_query_range('SELECT * FROM {menu_router} WHERE path IN (:ancestors) ORDER BY fit DESC', 0, 1, array(':ancestors' => $ancestors))->fetchAssoc();

      if ($router_item) {
        // Allow modules to alter the router item before it is translated and
        // checked for access.
        drupal_alter('menu_get_item', $router_item, $path, $original_map);

        $router_item['original_map'] = $original_map;
        if ($original_map === FALSE) {
          $router_items[$path] = FALSE;
          return FALSE;
        }
        $router_item['map'] = $original_map;
        $router_item['page_arguments'] = array_merge(menu_unserialize($router_item['page_arguments'], $original_map), array_slice($original_map, $router_item['number_parts']));
        $router_item['theme_arguments'] = array_merge(menu_unserialize($router_item['theme_arguments'], $original_map), array_slice($original_map, $router_item['number_parts']));
      }
      $router_items[$path] = $router_item;
    }
    return $router_items[$path];
  }

  /**
   * Get the resource name and version from the page arguments in the router.
   *
   * @param string $path
   *   The path to match the router item. Leave it empty to use the current one.
   *
   * @return array
   *   An array of 2 elements with the page arguments.
   */
  public static function getPageArguments($path = NULL) {
    $router_item = static::getMenuItem($path);
    $output = array(NULL, NULL);
    if (empty($router_item['page_arguments'])) {
      return $output;
    }
    $page_arguments = $router_item['page_arguments'];

    $index = 0;
    foreach ($page_arguments as $page_argument) {
      $output[$index] = $page_argument;
      $index++;
      if ($index >= 2) {
        break;
      }
    }

    return $output;
  }
  /**
   * Parses the version string.
   *
   * @param string $version
   *   The string containing the version information.
   * @param string $resource_name
   *   (optional) Name of the resource to get the latest minor version.
   *
   * @return array
   *   Numeric array with major and minor version.
   */
  public static function parseVersionString($version, $resource_name = NULL) {
    if (preg_match('/^v\d+(\.\d+)?$/', $version)) {
      // Remove the leading 'v'.
      $version = substr($version, 1);
    }
    $output = explode('.', $version);
    if (count($output) == 1) {
      $major_version = $output[0];
      // Abort if the version is not numeric.
      if (!$resource_name || !ctype_digit((string) $major_version)) {
        return;
      }
      // Get the latest version for the resource.
      return static::getResourceLastVersion($resource_name, $major_version);
    }
    // Abort if any of the versions is not numeric.
    if (!ctype_digit((string) $output[0]) || !ctype_digit((string) $output[1])) {
      return;
    }
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function index() {
    $this->notImplementedCrudOperation(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function view($id) {
    $this->notImplementedCrudOperation(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $ids) {
    $output = array();
    foreach ($ids as $id) {
      $output[] = $this->view($id);
    }
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function create() {
    $this->notImplementedCrudOperation(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function update($ids, $full_replace = FALSE) {
    $this->notImplementedCrudOperation(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function remove($id) {
    $this->notImplementedCrudOperation(__FUNCTION__);
  }

  /**
   * Helper method with the code to run for non implemented CRUD operations.
   *
   * @param string $operation
   *   The crud operation.
   *
   * @throws \RestfulNotImplementedException
   */
  protected static function notImplementedCrudOperation($operation) {
    // The default behavior is to not support the crud action.
    throw new \RestfulNotImplementedException(format_string('The "@method" method is not implemented in class @class.', array('@method' => $operation, '@class' => __CLASS__)));
  }

  /**
   * Overrides the range parameter with the URL value if any.
   *
   * @throws RestfulBadRequestException
   */
  protected function overrideRange() {
    $request = $this->getRequest();
    if (!empty($request['range'])) {
      $url_params = $this->getPluginKey('url_params');
      if (!$url_params['range']) {
        throw new \RestfulBadRequestException('The range parameter has been disabled in server configuration.');
      }

      if (!ctype_digit((string) $request['range']) || $request['range'] < 1) {
        throw new \RestfulBadRequestException('"Range" property should be numeric and higher than 0.');
      }
      if ($request['range'] < $this->getRange()) {
        // If there is a valid range property in the request override the range.
        $this->setRange($request['range']);
      }
    }
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
  public final static function isArrayNumeric(array $input) {
    foreach (array_keys($input) as $key) {
      if (!ctype_digit((string) $key)) {
        return FALSE;
      }
    }
    return TRUE;
  }

}

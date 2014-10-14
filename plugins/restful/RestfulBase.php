<?php

/**
 * @file
 * Contains RestfulBase.
 */

abstract class RestfulBase implements \RestfulInterface {

  /**
   * Nested array that provides information about what method to call for each
   * route pattern.
   *
   * @var array $controllers
   */
  protected $controllers = array();

  /**
   * The plugin definition.
   *
   * @var array $plugin
   */
  protected $plugin;

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
    return array(
      '' => array(
        // Return the value from the non-entity resource.
        \RestfulInterface::GET => 'viewNonEntityResourceValue',
      ),
    );
  }

  /**
   * Return the value of the non-entity resource.
   *
   * @return array
   *   Array with the public fields populated.
   */
  protected function viewNonEntityResourceValue() {
    foreach ($this->getPublicFields() as $public_property => $info) {
      $value = NULL;

      if ($info['callback']) {
        $value = static::executeCallback($info['callback']);
      }

      if ($value && $info['process_callbacks']) {
        foreach ($info['process_callbacks'] as $process_callback) {
          $value = static::executeCallback($process_callback, array($value));
        }
      }

      $values[$public_property] = $value;
    }

    return $values;
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
    $this->authenticationManager = $auth_manager ? $auth_manager : new \RestfulAuthenticationManager();
    $this->cacheController = $cache_controller ? $cache_controller : $this->newCacheObject();
    if (!empty($plugin['rate_limit'])) {
      $this->setRateLimitManager(new \RestfulRateLimitManager($plugin['resource'], $plugin['rate_limit']));
    }
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
   * Gets information about the restful plugin.
   *
   * @param string
   *   (optional) The name of the key to return.
   *
   * @return mixed
   *   Depends on the requested value.
   */
  public function getPluginInfo($key = NULL) {
    if (isset($key)) {
      return empty($this->plugin[$key]) ? NULL : $this->plugin[$key];
    }
    return $this->plugin;
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
    $formatter_handler = restful_output_format($this);
    return $formatter_handler->format($data);
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
    return NULL;
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
   */
  public function options($path = '', array $request = array()) {
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

    // TODO: Allow configuring the allowed origins and return them here.
    $this->setHttpHeaders('Access-Control-Allow-Origin', '*');

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
   * Adds query tags and metadata to the EntityFieldQuery.
   *
   * @param \EntityFieldQuery $query
   *   The query to enhance.
   */
  protected function addExtraInfoToQuery(\EntityFieldQuery $query) {
    // Add a generic tags to the query.
    $query->addTag('restful');
    $query->addMetaData('account', $this->getAccount());
  }

  /**
   * {@inheritdoc}
   */
  public function getPublicFields() {
    $public_fields = $this->publicFieldsInfo();
    // Set defaults values.
    foreach (array_keys($public_fields) as $key) {
      // Set default values.
      $info = &$public_fields[$key];
      $info += array(
        'process_callbacks' => array(),
        'callback' => FALSE,
      );
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

    $cache_info = $this->getPluginInfo('render_cache');
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
    $cache_info = $this->getPluginInfo('render_cache');
    if (!$cache_info['render']) {
      return;
    }

    $cid = $this->generateCacheId($context);
    return $this->getCacheController()->get($cid);
  }

  /**
   * Store a rendered entity into the cache.
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
    $cache_info = $this->getPluginInfo('render_cache');
    if (!$cache_info['render']) {
      return;
    }

    $cid = $this->generateCacheId($context);
    $this->getCacheController()->set($cid, $data, $cache_info['expire']);
  }

  /**
   * Generate a cache identifier for the request and the current entity.
   *
   * @param array $context
   *   An associative array with additional information to build the cache ID.
   *
   * @return string
   *   The cache identifier.
   */
  protected function generateCacheId(array $context = array()) {
    // Get the cache ID from the selected params. We will use a complex cache ID
    // for smarter invalidation. The cache id will be like:
    // v<major version>.<minor version>::uu<user uid>::pa<params array>
    // The code before every bit is a 2 letter representation of the label. For
    // instance, the params array will be something like:
    // fi:id,title::re:admin
    // When the request has ?fields=id,title&restrict=admin
    $version = $this->getVersion();
    $cid = 'v' . $version['major'] . '.' . $version['minor'] . '::uu' . $this->getAccount()->uid . '::pa';
    $cid_params = array();
    $request = $this->getRequest();
    static::cleanRequest($request);
    $options = $context + $request;
    foreach ($options as $param => $value) {
      // Some request parameters don't affect how the resource is rendered, this
      // means that we should skip them for the cache ID generation.
      if (in_array($param, array('page', 'sort', 'q', '__application', 'filter'))) {
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
    $cache_info = $this->getPluginInfo('render_cache');
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

  /**
   * Returns the names of the available formatter plugins.
   *
   * @return array
   *   Array of formatter names.
   */
  public function formatterNames() {
    $plugin_info = $this->getPluginInfo();
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
    // Sort based on the minor version.
    ksort($resources, SORT_NUMERIC);
    // Get the latest resource for the minor version.
    $resource = end($resources);
    return array($resource['major_version'], $resource['minor_version']);
  }

}

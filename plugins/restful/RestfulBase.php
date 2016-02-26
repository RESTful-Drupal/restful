<?php

/**
 * @file
 * Contains RestfulBase.
 */

use Drupal\restful\Authentication\AuthenticationManager;
use Drupal\restful\Exception\BadRequestException;
use Drupal\restful\Exception\ForbiddenException;
use Drupal\restful\Exception\GoneException;
use Drupal\restful\Exception\NotImplementedException;
use Drupal\restful\Exception\RestfulException;
use Drupal\restful\Exception\ServiceUnavailableException;
use Drupal\restful\Formatter\FormatterManager;
use Drupal\restful\Plugin\FormatterPluginManager;
use Drupal\restful\RateLimit\RateLimitManager;

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
   * @var AuthenticationManager
   */
  protected $authenticationManager;

  /**
   * Rate limit manager.
   *
   * @var RateLimitManager
   */
  protected $rateLimitManager = NULL;

  /**
   * Rate limit manager.
   *
   * @var FormatterManager
   */
  protected $formatterManager = NULL;

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
   * @param AuthenticationManager $authenticationManager
   */
  public function setAuthenticationManager(AuthenticationManager $authenticationManager) {
    $this->authenticationManager = $authenticationManager;
  }

  /**
   * Getter for $authenticationManager.
   *
   * @return AuthenticationManager
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
   * @param RateLimitManager $rateLimitManager
   */
  public function setRateLimitManager($rateLimitManager) {
    $this->rateLimitManager = $rateLimitManager;
  }

  /**
   * Getter for rateLimitManager.
   *
   * @return RateLimitManager
   */
  public function getRateLimitManager() {
    return $this->rateLimitManager;
  }

  /**
   * Returns the formatter manager.
   *
   * @return FormatterManager
   */
  public function getFormatterManager() {
    return $this->formatterManager;
  }

  /**
   * Constructs a RestfulEntityBase object.
   *
   * @param array $plugin
   *   Plugin definition.
   * @param AuthenticationManager $auth_manager
   *   (optional) Injected authentication manager.
   * @param DrupalCacheInterface $cache_controller
   *   (optional) Injected cache backend.
   */
  public function __construct(array $plugin, AuthenticationManager $auth_manager = NULL, \DrupalCacheInterface $cache_controller = NULL, $langcode = NULL) {
    parent::__construct($plugin);
    $this->authenticationManager = $auth_manager ? $auth_manager : new AuthenticationManager();
    $this->cacheController = $cache_controller ? $cache_controller : $this->newCacheObject();
    if ($rate_limit = $this->getPluginKey('rate_limit')) {
      $this->setRateLimitManager(new RateLimitManager($this, $rate_limit));
    }
    $this->formatterManager = new FormatterManager($this);
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
   * @throws ServiceUnavailableException
   */
  protected function processDataProviderOptions($required_keys = array(), $default_values = array()) {
    $options = $this->getPluginKey('data_provider_options');
    $params = array('@class' => get_class($this));
    // Check required keys exist.
    foreach ($required_keys as $key) {
      if (empty($options[$key])) {
        $params['@key'] = $key;
        throw new ServiceUnavailableException(format_string('@class is missing "@key" property in the "data_provider_options" key of the $plugin', $params));
      }
    }

    // Add default values.
    $options += $default_values;
    $this->setPluginKey('data_provider_options', $options);

    return $options;
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
    $formatters = $this->formatterManager->getPlugins();
    foreach ($formatter_names as $formatter_name) {
      $accepted_formats[] = $formatters->get($formatter_name)->getContentTypeHeader();
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
      throw new BadRequestException('Path does not exist');
    }

    if ($check_rate_limit && $this->getRateLimitManager()) {
      // This will throw the appropriate exception if needed.
      $this->getRateLimitManager()->checkRateLimit($request);
    }

    return $this->{$method_name}($this->path);
  }

  /**
   * Parses the request to get the sorting options.
   *
   * @return array
   *   With the different sorting options.
   *
   * @throws BadRequestException
   */
  protected function parseRequestForListSort() {
    $request = $this->getRequest();
    $public_fields = $this->getPublicFields();

    if (empty($request['sort'])) {
      return array();
    }
    $url_params = $this->getPluginKey('url_params');
    if (!$url_params['sort']) {
      throw new BadRequestException('Sort parameters have been disabled in server configuration.');
    }

    $sorts = array();
    foreach (explode(',', $request['sort']) as $sort) {
      $direction = $sort[0] == '-' ? 'DESC' : 'ASC';
      $sort = str_replace('-', '', $sort);
      // Check the sort is on a legal key.
      if (empty($public_fields[$sort])) {
        throw new BadRequestException(format_string('The sort @sort is not allowed for this path.', array('@sort' => $sort)));
      }

      $sorts[$sort] = $direction;
    }
    return $sorts;
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
   * Invalidates cache for a certain entity.
   *
   * @param string $cid
   *   The wildcard cache id to invalidate. Do not add * for the wildcard.
   */
  public function cacheInvalidate($cid) {
    $cache_info = $this->getPluginKey('render_cache');
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
    $plugin_info = $this->getPlugin();
    if (!empty($plugin_info['formatter'])) {
      // If there is formatter info in the plugin definition, return that.
      return array($plugin_info['formatter']);
    }
    // If there is no formatter info in the plugin definition, return a list
    // of all the formatters available.
    $formatter_names = array();
    $formatter_manager = FormatterPluginManager::create();

    foreach ($formatter_manager->getDefinitions() as $formatter_info) {
      $formatter_names[] = $formatter_info['id'];
    }
    return $formatter_names;
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
   * @throws NotImplementedException
   */
  protected static function notImplementedCrudOperation($operation) {
    // The default behavior is to not support the crud action.
    throw new NotImplementedException(format_string('The "@method" method is not implemented in class @class.', array('@method' => $operation, '@class' => __CLASS__)));
  }

  /**
   * Overrides the range parameter with the URL value if any.
   *
   * @throws BadRequestException
   */
  protected function overrideRange() {
    $request = $this->getRequest();
    if (!empty($request['range'])) {
      $url_params = $this->getPluginKey('url_params');
      if (!$url_params['range']) {
        throw new BadRequestException('The range parameter has been disabled in server configuration.');
      }

      if (!ctype_digit((string) $request['range']) || $request['range'] < 1) {
        throw new BadRequestException('"Range" property should be numeric and higher than 0.');
      }
      if ($request['range'] < $this->getRange()) {
        // If there is a valid range property in the request override the range.
        $this->setRange($request['range']);
      }
    }
  }

}

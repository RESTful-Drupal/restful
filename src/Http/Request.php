<?php

/**
 * @file
 * Contains \Drupal\restful\Http\Request
 */

namespace Drupal\restful\Http;
use Drupal\restful\Exception\BadRequestException;
use Drupal\restful\Util\StringHelper;

/**
 * Deals with everything coming from the consumer.
 */
class Request implements RequestInterface {

  /**
   * Names for headers that can be trusted when
   * using trusted proxies.
   *
   * The default names are non-standard, but widely used
   * by popular reverse proxies (like Apache mod_proxy or Amazon EC2).
   */
  protected static $trustedHeaders = array(
    self::HEADER_CLIENT_IP => 'X_FORWARDED_FOR',
    self::HEADER_CLIENT_HOST => 'X_FORWARDED_HOST',
    self::HEADER_CLIENT_PROTO => 'X_FORWARDED_PROTO',
    self::HEADER_CLIENT_PORT => 'X_FORWARDED_PORT',
  );

  protected static $trustedProxies = array();

  /**
   * HTTP Method.
   *
   * @var string
   */
  protected $method;

  /**
   * URI (path and query string).
   *
   * @var string
   */
  protected $uri;

  /**
   * Path
   *
   * @var string
   */
  protected $path;

  /**
   * Query parameters.
   *
   * @var array
   */
  protected $query;

  /**
   * The input HTTP headers.
   *
   * @var HttpHeaderBag
   */
  protected $headers;

  /**
   * The unprocessed body of the request.
   *
   * This should be a PHP stream, but let's keep it simple.
   *
   * @var string
   */
  protected $body;

  /**
   * Indicates if the request was routed by the menu system.
   *
   * @var bool
   */
  protected $viaRouter = FALSE;

  /**
   * The passed in CSRF token in the corresponding header.
   *
   * @var string
   */
  protected $csrfToken = NULL;

  /**
   * Cookies for the request.
   *
   * @var array
   */
  protected $cookies = array();

  /**
   * Files attached to the request.
   *
   * @var array
   */
  protected $files = array();

  /**
   * Server information.
   *
   * @var array
   */
  protected $server = array();

  /**
   * Holds the parsed body.
   *
   * @var array
   */
  private $parsedBody;

  /**
   * Holds the parsed input via URL.
   *
   * @internal
   * @var \ArrayObject
   */
  private $parsedInput;

  /**
   * Store application data as part of the request.
   *
   * @var array
   */
  protected $applicationData = array();

  /**
   * Constructor.
   *
   * Parses the URL and the query params. It also uses input:// to get the body.
   */
  public function __construct($path, array $query, $method = 'GET', HttpHeaderBag $headers, $via_router = FALSE, $csrf_token = NULL, array $cookies = array(), array $files = array(), array $server = array(), $parsed_body = NULL) {
    $this->path = $path;
    $this->query = !isset($query) ? static::parseInput() : $query;
    $this->query = $this->fixQueryFields($this->query);
    // If the method is empty, fall back to GET.
    $this->method = $method ?: static::METHOD_GET;
    $this->headers = $headers;
    $this->viaRouter = $via_router;
    $this->csrfToken = $csrf_token;
    $this->cookies = $cookies;
    $this->files = $files;
    $this->server = $server;
    $this->parsedBody = $parsed_body;

    // Allow implementing modules to alter the request.
    drupal_alter('restful_parse_request', $this);
  }

  /**
   * {@inheritdoc}
   */
  public static function create($path, array $query = array(), $method = 'GET', HttpHeaderBag $headers = NULL, $via_router = FALSE, $csrf_token = NULL, array $cookies = array(), array $files = array(), array $server = array(), $parsed_body = NULL) {
    if (!$headers) {
      $headers = new HttpHeaderBag();
    }
    if (($overridden_method = strtoupper($headers->get('x-http-method-override')->getValueString())) && ($method == static::METHOD_POST)) {
      if (!static::isValidMethod($overridden_method)) {
        throw new BadRequestException(sprintf('Invalid overridden method: %s.', $overridden_method));
      }
      $method = $overridden_method;
    }
    return new static($path, $query, $method, $headers, $via_router, $csrf_token, $cookies, $files, $server, $parsed_body);
  }

  /**
   * {@inheritdoc}
   */
  public static function createFromGlobals() {
    $path = implode('/', arg());
    $query =  drupal_get_query_parameters();
    $method = strtoupper($_SERVER['REQUEST_METHOD']);

    // This flag is used to identify if the request is done "via Drupal" or "via
    // CURL";
    $via_router = TRUE;
    $headers = static::parseHeadersFromGlobals();
    $csrf_token = $headers->get('x-csrf-token')->getValueString();

    return static::create($path, $query, $method, $headers, $via_router, $csrf_token, $_COOKIE, $_FILES, $_SERVER);
  }

  /**
   * {@inheritdoc}
   */
  public static function isWriteMethod($method) {
    $method = strtoupper($method);
    return in_array($method, array(
      static::METHOD_PUT,
      static::METHOD_POST,
      static::METHOD_PATCH,
      static::METHOD_DELETE,
    ));
  }

  /**
   * {@inheritdoc}
   */
  public static function isReadMethod($method) {
    $method = strtoupper($method);
    return in_array($method, array(
      static::METHOD_GET,
      static::METHOD_HEAD,
      static::METHOD_OPTIONS,
      static::METHOD_TRACE,
      static::METHOD_CONNECT,
    ));
  }

  /**
   * {@inheritdoc}
   */
  public static function isValidMethod($method) {
    $method = strtolower($method);
    return static::isReadMethod($method) || static::isWriteMethod($method);
  }

  /**
   * {@inheritdoc}
   */
  public function isListRequest($resource_path) {
    if ($this->method != static::METHOD_GET) {
      return FALSE;
    }
    return empty($resource_path) || strpos($resource_path, ',') !== FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getParsedBody() {
    if ($this->parsedBody) {
      return $this->parsedBody;
    }
    // Find out the body format and parse it into the \ArrayObject.
    $this->parsedBody = $this->parseBody($this->method);
    return $this->parsedBody;
  }

  /**
   * {@inheritdoc}
   */
  public function getParsedInput() {
    if (isset($this->parsedInput)) {
      return $this->parsedInput;
    }
    // Get the input data provided via URL.
    $this->parsedInput = $this->query;
    unset($this->parsedInput['q']);
    return $this->parsedInput;
  }

  /**
   * {@inheritdoc}
   */
  public function getPagerInput() {
    $input = $this->getParsedInput();
    if (!isset($input['page'])) {
      $page = array('number' => 1);
    }
    else {
      $page = $input['page'];
      if (!is_array($page)) {
        $page = array('number' => $page);
      }
    }
    if (isset($input['range'])) {
      $page['size'] = $input['range'];
    }
    return $page + array('number' => 1);
  }

  /**
   * {@inheritdoc}
   */
  public function setParsedInput(array $input) {
    $this->parsedInput = $input;
  }

  /**
   * Parses the body.
   *
   * @param string $method
   *   The HTTP method.
   *
   * @return array
   *   The parsed body.
   */
  protected function parseBody($method) {
    if (!static::isWriteMethod($method)) {
      return NULL;
    }
    $content_type = $this
      ->getHeaders()
      ->get('Content-Type')
      ->get();

    $content_type = reset($content_type);
    $content_type = $content_type ?: 'application/x-www-form-urlencoded';
    return static::parseBodyContentType($content_type);
  }

  /**
   * Parses the provided payload according to a content type.
   *
   * @param string $content_type
   *   The contents of the Content-Type header.
   *
   * @return array
   *   The parsed body.
   *
   * @throws \Drupal\restful\Exception\BadRequestException
   */
  protected static function parseBodyContentType($content_type) {
    if (!$input_string = file_get_contents('php://input')) {
      return NULL;
    }
    if ($content_type == 'application/x-www-form-urlencoded') {
      $body = NULL;
      parse_str($input_string, $body);
      return $body;
    }
    // Use the Content Type header to negotiate a formatter to parse the body.
    $formatter = restful()
      ->getFormatterManager()
      ->negotiateFormatter($content_type);
    return $formatter->parseBody($input_string);
  }

  /**
   * Parses the input data.
   *
   * @return array
   *   The parsed input.
   */
  protected static function parseInput() {
    return $_GET;
  }

  /**
   * Helps fixing the fields to ensure that dot-notation makes sense.
   *
   * Make sure to add all of the parents for the dot-notation sparse
   * fieldsets. fields=active,image.category.name,image.description becomes
   * fields=active,image,image.category,image.category.name,image.description
   *
   * @param array $input
   *   The parsed input to fix.
   *
   * @return array
   *   The parsed input array.
   */
  protected function fixQueryFields(array $input) {
    // Make sure that we include all the parents for full linkage.
    foreach (array('fields', 'include') as $key_name) {
      if (empty($input[$key_name])) {
        continue;
      }
      $added_keys = array();
      foreach (explode(',', $input[$key_name]) as $key) {
        $parts = explode('.', $key);
        for ($index = 0; $index < count($parts); $index++) {
          $path = implode('.', array_slice($parts, 0, $index + 1));
          $added_keys[$path] = TRUE;
        }
      }
      $input[$key_name] = implode(',', array_keys(array_filter($added_keys))) ?: NULL;
    }

    return $input;
  }

  /**
   * Parses the header names and values from globals.
   *
   * @return HttpHeaderBag
   *   The headers.
   */
  protected static function parseHeadersFromGlobals() {
    $bag = new HttpHeaderBag();
    $headers = array();
    if (function_exists('apache_request_headers')) {
      $headers = apache_request_headers();
    }
    else {
      $content_header_keys = array('CONTENT_TYPE', 'CONTENT_LENGTH');
      foreach ($_SERVER as $key => $value) {
        if (strpos($key, 'HTTP_') === 0 || in_array($key, $content_header_keys)) {
          // Generate the plausible header name based on the $name.
          // Converts 'HTTP_X_FORWARDED_FOR' to 'X-Forwarded-For'
          $name = preg_replace('/^HTTP_/', '', $key);
          $parts = explode('_', $name);
          $parts = array_map('strtolower', $parts);
          $parts = array_map('ucfirst', $parts);
          $name = implode('-', $parts);
          $headers[$name] = $value;
        }
      }
    }
    // Iterate over the headers and bag them.
    foreach ($headers as $name => $value) {
      $bag->add(HttpHeader::create($name, $value));
    }
    return $bag;
  }

  /**
   * {@inheritdoc}
   */
  public function getPath($strip = TRUE) {
    // Remove the restful prefix from the beginning of the path.
    if ($strip && strpos($this->path, variable_get('restful_hook_menu_base_path', 'api')) !== FALSE) {
      return substr($this->path, strlen(variable_get('restful_hook_menu_base_path', 'api')) + 1);
    }
    return $this->path;
  }

  /**
   * {@inheritdoc}
   */
  public function href() {
    return url($this->path, array(
      'absolute' => TRUE,
      'query' => $this->query,
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function getHeaders() {
    return $this->headers;
  }

  /**
   * Get the credentials based on the $_SERVER variables.
   *
   * @return array
   *   A numeric array with the username and password.
   */
  protected static function getCredentials() {
    $username = empty($_SERVER['PHP_AUTH_USER']) ? NULL : $_SERVER['PHP_AUTH_USER'];
    $password = empty($_SERVER['PHP_AUTH_PW']) ? NULL : $_SERVER['PHP_AUTH_PW'];

    // Try to fill PHP_AUTH_USER & PHP_AUTH_PW with REDIRECT_HTTP_AUTHORIZATION
    // for compatibility with Apache PHP CGI/FastCGI.
    // This requires the following line in your ".htaccess"-File:
    // RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
    $authorization_header = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : NULL;
    $authorization_header = $authorization_header ?: (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) ? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] : NULL);
    if (!empty($authorization_header) && !isset($username) && !isset($password)) {
      if (!$token = StringHelper::removePrefix('Basic ', $authorization_header)) {
        return NULL;
      }
      $authentication = base64_decode($token);
      list($username, $password) = explode(':', $authentication);
      $_SERVER['PHP_AUTH_USER'] = $username;
      $_SERVER['PHP_AUTH_PW'] = $password;
    }

    return array($username, $password);
  }

  /**
   * {@inheritdoc}
   */
  public function getUser() {
    list($account,) = static::getCredentials();
    return $account;
  }

  /**
   * {@inheritdoc}
   */
  public function getPassword() {
    list(, $password) = static::getCredentials();
    return $password;
  }

  /**
   * {@inheritdoc}
   */
  public function getMethod() {
    return $this->method;
  }

  /**
   * {@inheritdoc}
   */
  public function setMethod($method) {
    $this->method = strtoupper($method);
  }

  /**
   * {@inheritdoc}
   */
  public function getServer() {
    return $this->server;
  }

  /**
   * {@inheritdoc}
   */
  public function setApplicationData($key, $value) {
    $this->applicationData[$key] = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function clearApplicationData() {
    $this->applicationData = array();
  }

  /**
   * {@inheritdoc}
   */
  public function getApplicationData($key) {
    if (!isset($this->applicationData[$key])) {
      return NULL;
    }
    return $this->applicationData[$key];
  }

  /**
   * {@inheritdoc}
   */
  public function isSecure() {
    if (self::$trustedProxies && self::$trustedHeaders[self::HEADER_CLIENT_PROTO] && $proto = $this->headers->get(self::$trustedHeaders[self::HEADER_CLIENT_PROTO])->getValueString()) {
      return in_array(strtolower(current(explode(',', $proto))), array('https', 'on', 'ssl', '1'));
    }
    $https = $this->server['HTTPS'];
    return !empty($https) && strtolower($https) !== 'off';
  }

  /**
   * {@inheritdoc}
   */
  public function getCookies() {
    return $this->cookies;
  }

  /**
   * {@inheritdoc}
   */
  public function getFiles() {
    return $this->files;
  }

  /**
   * {@inheritdoc}
   */
  public function getCsrfToken() {
    return $this->csrfToken;
  }

  /**
   * {@inheritdoc}
   */
  public function isViaRouter() {
    return $this->viaRouter;
  }

  /**
   * {@inheritdoc}
   */
  public function setViaRouter($via_router) {
    $this->viaRouter = $via_router;
  }

}

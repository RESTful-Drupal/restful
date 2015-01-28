<?php

/**
 * @file
 * Contains \Drupal\restful\Http\Request
 */

namespace Drupal\restful\Http;

/**
 * Deals with everything coming from the consumer.
 */
class Request implements RequestInterface {

  const METHOD_HEAD = 'HEAD';
  const METHOD_GET = 'GET';
  const METHOD_POST = 'POST';
  const METHOD_PUT = 'PUT';
  const METHOD_PATCH = 'PATCH';
  const METHOD_DELETE = 'DELETE';
  const METHOD_PURGE = 'PURGE';
  const METHOD_OPTIONS = 'OPTIONS';
  const METHOD_TRACE = 'TRACE';
  const METHOD_CONNECT = 'CONNECT';

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
   * @internal
   * @var \ArrayObject
   */
  private $parsedBody;

  /**
   * Constructor.
   *
   * Parses the URL and the query params. It also uses input:// to get the body.
   */
  public function __construct($path, $query, $method = 'GET', HttpHeaderBag $headers, $via_router = FALSE, $csrf_token = NULL, $cookies = array(), $files = array(), $server = array()) {
    $this->path = $path;
    $this->query = $query;
    $this->method = $method;
    $this->headers = $headers;
    $this->viaRouter = $via_router;
    $this->csrfToken = $csrf_token;
    $this->cookies = $cookies;
    $this->files = $files;
    $this->server = $server;

    // Allow implementing modules to alter the request.
    drupal_alter('restful_parse_request', $this);
  }

  /**
   * {@inheritdoc}
   */
  public static function create($path, $query, $method = 'GET', HttpHeaderBag $headers, $via_router = FALSE, $csrf_token = NULL, $cookies = array(), $files = array(), $server = array()) {
    if ($method == static::METHOD_POST && $headers->get('x-http-method-override')) {
      $method = $headers->get('x-http-method-override')->getValueString();
    }
    if (!static::isValidMethod($method)) {
      throw new \RestfulBadRequestException('Unrecognized HTTP method.');
    }
    return new static($path, $query, $method, $headers, $via_router, $csrf_token, $cookies, $files, $server);
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
    $csrf_token = $headers->get('x-csrf-Token')->getValueString();

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
  public function isListRequest() {
    if ($this->method != static::METHOD_GET) {
      return FALSE;
    }
    return empty($this->path) || strpos($this->path, ',') !== FALSE;
  }

  /**
   * Parses the body string.
   *
   * @return array
   */
  public function getParsedBody() {
    if ($this->parsedBody) {
      return $this->parsedBody;
    }
    // Find out the body format and parse it into the \ArrayObject.
    $this->parsedBody = static::parseBody($this->method);
    return $this->parsedBody;
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
  protected static function parseBody($method) {
    $body = NULL;
    if ($method == static::METHOD_GET) {
      return $_GET;
    }
    elseif ($method == static::METHOD_POST) {
      return $_POST;
    }

    if ($query_string = file_get_contents('php://input')) {
      // When trying to POST using curl on simpleTest it doesn't reach
      // $_POST, so we try to re-grab it here.
      // Also, sometimes the client might send the input still encoded.
      if ($decoded_json = drupal_json_decode($query_string)) {
        return $decoded_json;
      }
      else {
        parse_str($query_string, $body);
        return $body;
      }
    }

    return NULL;
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
      foreach ($_SERVER as $key => $value) {
        if (strpos($key, 'HTTP_') === 0) {
          // Generate the plausible header name based on the $name.
          // Converts 'HTTP_X_FORWARDED_FOR' to 'X-Forwarded-For'
          $name = substr($key, 5);
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
  public function getPath() {
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
    if (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) && !isset($username) && !isset($password)) {
      $authentication = base64_decode(substr($_SERVER['REDIRECT_HTTP_AUTHORIZATION'], 6));
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

}

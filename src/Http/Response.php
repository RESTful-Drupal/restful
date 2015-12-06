<?php

/**
 * @file
 * Contains \Drupal\restful\Http\Response.
 *
 * A lot of this has been extracted from the Symfony Response class.
 */

namespace Drupal\restful\Http;

use Drupal\restful\Exception\InternalServerErrorException;
use Drupal\restful\Exception\UnprocessableEntityException;

class Response implements ResponseInterface {

  /**
   * @var HttpHeaderBag
   */
  public $headers;

  /**
   * @var string
   */
  protected $content;

  /**
   * @var string
   */
  protected $version;

  /**
   * @var int
   */
  protected $statusCode;

  /**
   * @var string
   */
  protected $statusText;

  /**
   * @var string
   */
  protected $charset;

  /**
   * Status codes translation table.
   *
   * The list of codes is complete according to the
   * {@link http://www.iana.org/assignments/http-status-codes/ Hypertext Transfer Protocol (HTTP) Status Code Registry}
   * (last updated 2012-02-13).
   *
   * Unless otherwise noted, the status code is defined in RFC2616.
   *
   * @var array
   */
  public static $statusTexts = array(
    100 => 'Continue',
    101 => 'Switching Protocols',
    102 => 'Processing',            // RFC2518
    200 => 'OK',
    201 => 'Created',
    202 => 'Accepted',
    203 => 'Non-Authoritative Information',
    204 => 'No Content',
    205 => 'Reset Content',
    206 => 'Partial Content',
    207 => 'Multi-Status',          // RFC4918
    208 => 'Already Reported',      // RFC5842
    226 => 'IM Used',               // RFC3229
    300 => 'Multiple Choices',
    301 => 'Moved Permanently',
    302 => 'Found',
    303 => 'See Other',
    304 => 'Not Modified',
    305 => 'Use Proxy',
    306 => 'Reserved',
    307 => 'Temporary Redirect',
    308 => 'Permanent Redirect',    // RFC7238
    400 => 'Bad Request',
    401 => 'Unauthorized',
    402 => 'Payment Required',
    403 => 'Forbidden',
    404 => 'Not Found',
    405 => 'Method Not Allowed',
    406 => 'Not Acceptable',
    407 => 'Proxy Authentication Required',
    408 => 'Request Timeout',
    409 => 'Conflict',
    410 => 'Gone',
    411 => 'Length Required',
    412 => 'Precondition Failed',
    413 => 'Request Entity Too Large',
    414 => 'Request-URI Too Long',
    415 => 'Unsupported Media Type',
    416 => 'Requested Range Not Satisfiable',
    417 => 'Expectation Failed',
    418 => 'I\'m a teapot',                                               // RFC2324
    422 => 'Unprocessable Entity',                                        // RFC4918
    423 => 'Locked',                                                      // RFC4918
    424 => 'Failed Dependency',                                           // RFC4918
    425 => 'Reserved for WebDAV advanced collections expired proposal',   // RFC2817
    426 => 'Upgrade Required',                                            // RFC2817
    428 => 'Precondition Required',                                       // RFC6585
    429 => 'Too Many Requests',                                           // RFC6585
    431 => 'Request Header Fields Too Large',                             // RFC6585
    500 => 'Internal Server Error',
    501 => 'Not Implemented',
    502 => 'Bad Gateway',
    503 => 'Service Unavailable',
    504 => 'Gateway Timeout',
    505 => 'HTTP Version Not Supported',
    506 => 'Variant Also Negotiates (Experimental)',                      // RFC2295
    507 => 'Insufficient Storage',                                        // RFC4918
    508 => 'Loop Detected',                                               // RFC5842
    510 => 'Not Extended',                                                // RFC2774
    511 => 'Network Authentication Required',                             // RFC6585
  );

  /**
   * Constructor.
   *
   * @param mixed $content
   *   The response content, see setContent()
   * @param int $status
   *   The response status code
   * @param array $headers
   *   An array of response headers
   *
   * @throws UnprocessableEntityException
   *   When the HTTP status code is not valid
   */
  public function __construct($content = '', $status = 200, $headers = array()) {
    $this->headers = new HttpHeaderBag($headers);
    $this->setContent($content);
    $this->setStatusCode($status);
    $this->setProtocolVersion('1.0');
    if (!$this->headers->has('Date')) {
      $this->setDate(new \DateTime(NULL, new \DateTimeZone('UTC')));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create($content = '', $status = 200, $headers = array()) {
    return new static($content, $status, $headers);
  }

  /**
   * Returns the Response as an HTTP string.
   *
   * The string representation of the Response is the same as the
   * one that will be sent to the client only if the prepare() method
   * has been called before.
   *
   * @return string
   *   The Response as an HTTP string
   *
   * @see prepare()
   */
  public function __toString() {
    return
      sprintf('HTTP/%s %s %s', $this->version, $this->statusCode, $this->statusText) . "\r\n" .
      $this->headers . "\r\n" .
      $this->getContent();
  }

  /**
   * Is the response empty?
   *
   * @return bool
   */
  protected function isEmpty() {
    return in_array($this->statusCode, array(204, 304));
  }

  /**
   * Is response informative?
   *
   * @return bool
   */
  protected function isInformational() {
    return $this->statusCode >= 100 && $this->statusCode < 200;
  }

  /**
   * Is response successful?
   *
   * @return bool
   */
  protected function isSuccessful() {
    return $this->statusCode >= 200 && $this->statusCode < 300;
  }

  /**
   * Is response invalid?
   *
   * @return bool
   */
  protected function isInvalid() {
    return $this->statusCode < 100 || $this->statusCode >= 600;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(RequestInterface $request) {
    $headers = $this->headers;
    if ($this->isInformational() || $this->isEmpty()) {
      $this->setContent(NULL);
      $headers->remove('Content-Type');
      $headers->remove('Content-Length');
    }
    else {
      // Content-type based on the Request. The content type should have been
      // set in the RestfulFormatter.

      // Fix Content-Type
      $charset = $this->charset ?: 'UTF-8';
      $content_type = $headers->get('Content-Type')->getValueString();
      if (stripos($content_type, 'text/') === 0 && stripos($content_type, 'charset') === FALSE) {
        // add the charset
        $headers->add(HttpHeader::create('Content-Type', $content_type . '; charset=' . $charset));
      }
      // Fix Content-Length
      if ($headers->has('Transfer-Encoding')) {
        $headers->remove('Content-Length');
      }
      if ($request->getMethod() == RequestInterface::METHOD_HEAD) {
        // cf. RFC2616 14.13
        $length = $headers->get('Content-Length')->getValueString();
        $this->setContent(NULL);
        if ($length) {
          $headers->add(HttpHeader::create('Content-Length', $length));
        }
      }
    }
    // Fix protocol
    $server_info = $request->getServer();
    if ($server_info['SERVER_PROTOCOL'] != 'HTTP/1.0') {
      $this->setProtocolVersion('1.1');
    }
    // Check if we need to send extra expire info headers
    if ($this->getProtocolVersion() == '1.0' && $this->headers->get('Cache-Control')->getValueString() == 'no-cache') {
      $this->headers->add(HttpHeader::create('pragma', 'no-cache'));
      $this->headers->add(HttpHeader::create('expires', -1));
    }
    $this->ensureIEOverSSLCompatibility($request);
  }


  /**
   * {@inheritdoc}
   */
  public function setContent($content) {
    if ($content !== NULL && !is_string($content) && !is_numeric($content) && !is_callable(array($content, '__toString'))) {
      throw new InternalServerErrorException(sprintf('The Response content must be a string or object implementing __toString(), "%s" given.', gettype($content)));
    }
    $this->content = (string) $content;
  }

  /**
   * {@inheritdoc}
   */
  public function getContent() {
    return $this->content;
  }

  /**
   * {@inheritdoc}
   */
  public function setProtocolVersion($version) {
    $this->version = $version;
  }

  /**
   * {@inheritdoc}
   */
  public function getProtocolVersion() {
    return $this->version;
  }

  /**
   * {@inheritdoc}
   */
  public function send() {
    $this->sendHeaders();
    $this->sendContent();

    static::pageFooter();
  }

  /**
   * Sends HTTP headers.
   */
  protected function sendHeaders() {
    foreach ($this->headers as $key => $header) {
      /* @var HttpHeader $header */
      drupal_add_http_header($header->getName(), $header->getValueString());
    }
    drupal_add_http_header('Status', $this->getStatusCode());
  }

  /**
   * Sends content for the current web response.
   */
  protected function sendContent() {
    echo $this->content;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatusCode($code, $text = NULL) {
    $this->statusCode = $code = (int) $code;
    if ($this->isInvalid()) {
      throw new UnprocessableEntityException(sprintf('The HTTP status code "%s" is not valid.', $code));
    }
    if ($text === NULL) {
      $this->statusText = isset(self::$statusTexts[$code]) ? self::$statusTexts[$code] : '';
      return;
    }
    if ($text === FALSE) {
      $this->statusText = '';
      return;
    }
    $this->statusText = $text;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatusCode() {
    return $this->statusCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setCharset($charset) {
    $this->charset = $charset;
  }

  /**
   * {@inheritdoc}
   */
  public function getCharset() {
    return $this->charset;
  }

  /**
   * {@inheritdoc}
   */
  public function getHeaders() {
    return $this->headers;
  }

  /**
   * Checks if we need to remove Cache-Control for SSL encrypted downloads when using IE < 9.
   *
   * @link http://support.microsoft.com/kb/323308
   */
  protected function ensureIEOverSSLCompatibility(Request $request) {
    $server_info = $request->getServer();
    if (stripos($this->headers->get('Content-Disposition')->getValueString(), 'attachment') !== FALSE && preg_match('/MSIE (.*?);/i', $server_info['HTTP_USER_AGENT'], $match) == 1 && $request->isSecure() === TRUE) {
      if (intval(preg_replace("/(MSIE )(.*?);/", "$2", $match[0])) < 9) {
        $this->headers->remove('Cache-Control');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setDate(\DateTime $date) {
    $date->setTimezone(new \DateTimeZone('UTC'));
    $this->headers->add(HttpHeader::create('Date', $date->format('D, d M Y H:i:s') . ' GMT'));
  }

  /**
   * Performs end-of-request tasks.
   *
   * This function sets the page cache if appropriate, and allows modules to
   * react to the closing of the page by calling hook_exit().
   *
   * This is just a wrapper around drupal_page_footer() so extending classes can
   * override this method if necessary.
   *
   * @see drupal_page_footer().
   */
  protected static function pageFooter() {
    drupal_page_footer();
  }

}

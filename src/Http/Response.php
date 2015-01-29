<?php

/**
 * @file
 * Contains \Drupal\restful\Http\Response.
 *
 * A lot of this has been extracted from the Symfony Response class.
 */

namespace Drupal\restful\Http;

use Drupal\restful\Exception\InternalServerErrorException;
use Drupal\restful\Exception\ServerConfigurationException;
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
   * Is the response empty?
   *
   * @return bool
   */
  public function isEmpty() {
    return in_array($this->statusCode, array(204, 304));
  }

  /**
   * Is response informative?
   *
   * @return bool
   */
  public function isInformational() {
    return $this->statusCode >= 100 && $this->statusCode < 200;
  }

  /**
   * Is response successful?
   *
   * @return bool
   */
  public function isSuccessful() {
    return $this->statusCode >= 200 && $this->statusCode < 300;
  }

  /**
   * Is response invalid?
   *
   * @return bool
   */
  public function isInvalid() {
    return $this->statusCode < 100 || $this->statusCode >= 600;
  }

  /**
   * Prepares the Response before it is sent to the client.
   *
   * This method tweaks the Response to ensure that it is
   * compliant with RFC 2616. Most of the changes are based on
   * the Request that is "associated" with this Response.
   *
   * @param Request $request
   *   A Request instance
   *
   * @return Response
   *   The current response.
   */
  public function prepare(Request $request) {
    $headers = $this->headers;
    if ($this->isInformational() || $this->isEmpty()) {
      $this->setContent(NULL);
      $headers->remove('Content-Type');
      $headers->remove('Content-Length');
    } else {
      // Content-type based on the Request
      if (!$headers->has('Content-Type')) {
        $format = $request->getRequestFormat();
        if (NULL !== $format && $mimeType = $request->getMimeType($format)) {
          $headers->add(HttpHeader::create('Content-Type', $mimeType));
        }
      }
      // Fix Content-Type
      $charset = $this->charset ?: 'UTF-8';
      if (!$headers->has('Content-Type')) {
        $headers->add(HttpHeader::create('Content-Type', 'text/html; charset='.$charset));
      } elseif (0 === stripos($headers->get('Content-Type'), 'text/') && false === stripos($headers->get('Content-Type'), 'charset')) {
        // add the charset
        $headers->add(HttpHeader::create('Content-Type', $headers->get('Content-Type')->getValueString().'; charset='.$charset));
      }
      // Fix Content-Length
      if ($headers->has('Transfer-Encoding')) {
        $headers->remove('Content-Length');
      }
      if ($request->getMethod() == Request::METHOD_HEAD) {
        // cf. RFC2616 14.13
        $length = $headers->get('Content-Length');
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
    if ('1.0' == $this->getProtocolVersion() && 'no-cache' == $this->headers->get('Cache-Control')->getValueString()) {
      $this->headers->add(HttpHeader::create('pragma', 'no-cache'));
      $this->headers->add(HttpHeader::create('expires', -1));
    }
    $this->ensureIEOverSSLCompatibility($request);
    return $this;
  }


  /**
   * Sets the response content.
   *
   * Valid types are strings, numbers, NULL, and objects that implement a __toString() method.
   *
   * @param mixed $content Content that can be cast to string
   *
   * @return Response
   *
   * @throws InternalServerErrorException
   */
  public function setContent($content) {
    if (NULL !== $content && !is_string($content) && !is_numeric($content) && !is_callable(array($content, '__toString'))) {
      throw new InternalServerErrorException(sprintf('The Response content must be a string or object implementing __toString(), "%s" given.', gettype($content)));
    }
    $this->content = (string) $content;
    return $this;
  }

  /**
   * Gets the current response content.
   *
   * @return string Content
   */
  public function getContent() {
    return $this->content;
  }

  /**
   * Sets the HTTP protocol version (1.0 or 1.1).
   *
   * @param string $version The HTTP protocol version
   *
   * @return Response
   */
  public function setProtocolVersion($version) {
    $this->version = $version;
    return $this;
  }

  /**
   * Gets the HTTP protocol version.
   *
   * @return string The HTTP protocol version
   */
  public function getProtocolVersion() {
    return $this->version;
  }
  /**
   * Sets the response status code.
   *
   * @param int   $code HTTP status code
   * @param mixed $text HTTP status text
   *
   * If the status text is NULL it will be automatically populated for the known
   * status codes and left empty otherwise.
   *
   * @return Response
   *
   * @throws UnprocessableEntityException When the HTTP status code is not valid
   */
  public function setStatusCode($code, $text = NULL) {
    $this->statusCode = $code = (int) $code;
    if ($this->isInvalid()) {
      throw new UnprocessableEntityException(sprintf('The HTTP status code "%s" is not valid.', $code));
    }
    if ($text === NULL) {
      $this->statusText = isset(self::$statusTexts[$code]) ? self::$statusTexts[$code] : '';
      return $this;
    }
    if ($text === FALSE) {
      $this->statusText = '';
      return $this;
    }
    $this->statusText = $text;
    return $this;
  }

  /**
   * Retrieves the status code for the current web response.
   *
   * @return int Status code
   */
  public function getStatusCode() {
    return $this->statusCode;
  }

  /**
   * Sets the response charset.
   *
   * @param string $charset Character set
   *
   * @return Response
   */
  public function setCharset($charset) {
    $this->charset = $charset;
    return $this;
  }

  /**
   * Retrieves the response charset.
   *
   * @return string Character set
   */
  public function getCharset() {
    return $this->charset;
  }

  /**
   * Checks if we need to remove Cache-Control for SSL encrypted downloads when using IE < 9.
   *
   * @link http://support.microsoft.com/kb/323308
   */
  protected function ensureIEOverSSLCompatibility(Request $request) {
    $server_info = $request->getServer();
    if (false !== stripos($this->headers->get('Content-Disposition')->getValueString(), 'attachment') && preg_match('/MSIE (.*?);/i', $server_info['HTTP_USER_AGENT'], $match) == 1 && true === $request->isSecure()) {
      if (intval(preg_replace("/(MSIE )(.*?);/", "$2", $match[0])) < 9) {
        $this->headers->remove('Cache-Control');
      }
    }
  }

}

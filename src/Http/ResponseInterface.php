<?php

/**
 * @file
 * Contains \Drupal\restful\Http\ResponseInterface.
 */

namespace Drupal\restful\Http;

use Drupal\restful\Exception\InternalServerErrorException;
use Drupal\restful\Exception\UnprocessableEntityException;

interface ResponseInterface {

  /**
   * Factory.
   *
   * @param mixed $content
   *   The response content, see setContent()
   * @param int $status
   *   The response status code
   * @param array $headers
   *   An array of response headers
   *
   * @return ResponseInterface
   *   The created object.
   *
   * @throws UnprocessableEntityException
   *   When the HTTP status code is not valid
   */
  public static function create($content = '', $status = 200, $headers = array());

  /**
   * Prepares the Response before it is sent to the client.
   *
   * This method tweaks the Response to ensure that it is
   * compliant with RFC 2616. Most of the changes are based on
   * the Request that is "associated" with this Response.
   *
   * @param RequestInterface $request
   *   A Request instance
   */
  public function prepare(RequestInterface $request);

  /**
   * Sets the response content.
   *
   * Valid types are strings, numbers, NULL, and objects that implement a __toString() method.
   *
   * @param mixed $content Content that can be cast to string
   *
   * @throws InternalServerErrorException
   */
  public function setContent($content);

  /**
   * Gets the current response content.
   *
   * @return string Content
   */
  public function getContent();

  /**
   * Sets the HTTP protocol version (1.0 or 1.1).
   *
   * @param string $version The HTTP protocol version
   */
  public function setProtocolVersion($version);

  /**
   * Gets the HTTP protocol version.
   *
   * @return string The HTTP protocol version
   */
  public function getProtocolVersion();

  /**
   * Sends HTTP headers and content.
   */
  public function send();

  /**
   * Sets the response status code.
   *
   * @param int $code
   *   HTTP status code
   * @param mixed $text
   *   HTTP status text
   *
   * If the status text is NULL it will be automatically populated for the known
   * status codes and left empty otherwise.
   *
   * @throws UnprocessableEntityException When the HTTP status code is not valid
   */
  public function setStatusCode($code, $text = NULL);

  /**
   * Retrieves the status code for the current web response.
   *
   * @return int Status code
   */
  public function getStatusCode();

  /**
   * Sets the response charset.
   *
   * @param string $charset Character set
   */
  public function setCharset($charset);

  /**
   * Retrieves the response charset.
   *
   * @return string Character set
   */
  public function getCharset();

  /**
   * Get the headers bag.
   *
   * @return HttpHeaderBag
   */
  public function getHeaders();

  /**
   * Sets the Date header.
   *
   * @param \DateTime $date
   *   A \DateTime instance
   */
  public function setDate(\DateTime $date);

}

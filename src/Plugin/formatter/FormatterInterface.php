<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\formatter\FormatterInterface
 */

namespace Drupal\restful\Plugin\formatter;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\restful\Exception\ServerConfigurationException;
use Drupal\restful\Plugin\resource\Field\ResourceFieldInterface;
use Drupal\restful\Plugin\resource\ResourceInterface;

interface FormatterInterface extends PluginInspectionInterface, ConfigurablePluginInterface {

  /**
   * Massages the raw data to create a structured array to pass to the renderer.
   *
   * @param ResourceFieldInterface[] $data
   *   The raw data to return.
   *
   * @return array
   *   The data prepared to be rendered.
   */
  public function prepare(array $data);

  /**
   * Renders an array in the selected format.
   *
   * @param array $structured_data
   *   The data prepared to be rendered as returned by
   *   \RestfulFormatterInterface::prepare().
   *
   * @return string
   *   The body contents for the HTTP response.
   */
  public function render(array $structured_data);

  /**
   * Formats the un-structured data into the output format.
   *
   * @param array $data
   *   The raw data to return.
   *
   * @return string
   *   The body contents for the HTTP response.
   *
   * @see \RestfulFormatterInterface::prepare()
   * @see \RestfulFormatterInterface::render()
   */
  public function format(array $data);

  /**
   * Returns the content type for the selected output format.
   *
   * @return string
   *   The contents for the ContentType header in the response.
   */
  public function getContentTypeHeader();

  /**
   * Gets the underlying resource.
   *
   * @return ResourceInterface
   *   The resource.
   */
  public function getResource();

  /**
   * Sets the underlying resource.
   *
   * @param ResourceInterface $resource
   *   The resource to set.
   */
  public function setResource(ResourceInterface $resource);

  /**
   * Parses the body string into the common format.
   *
   * @param string $body
   *   The string sent from the consumer.
   *
   * @return array
   *   The parsed object following the expected structure.
   *
   * @throws \Drupal\restful\Exception\ServerConfigurationException
   * @throws \Drupal\restful\Exception\BadRequestException
   */
  public function parseBody($body);

}

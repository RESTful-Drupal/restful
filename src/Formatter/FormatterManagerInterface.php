<?php

/**
 * @file
 * Contains \Drupal\restful\Formatter\FormatterManagerInterface
 */

namespace Drupal\restful\Formatter;

use Drupal\restful\Plugin\formatter\FormatterInterface;
use Drupal\restful\Plugin\resource\ResourceInterface;

interface FormatterManagerInterface {

  /**
   * Sets the resource.
   *
   * @param ResourceInterface $resource
   *   The resource.
   */
  public function setResource($resource);

  /**
   * Call the output format on the given data.
   *
   * @param array $data
   *   The array of data to format.
   * @param string $formatter_name
   *   The name of the formatter for the current resource. Leave it NULL to use
   *   the Accept headers.
   *
   * @return string
   *   The formatted output.
   */
  public function format(array $data, $formatter_name = NULL);

  /**
   * Call the output format on the given data.
   *
   * @param array $data
   *   The array of data to render.
   * @param string $formatter_name
   *   The name of the formatter for the current resource. Leave it NULL to use
   *   the Accept headers.
   *
   * @return string
   *   The rendered output.
   */
  public function render(array $data, $formatter_name = NULL);

  /**
   * Helper function to get the default output format from the current request.
   *
   * @param string $accept
   *   The Accept header.
   * @param string $formatter_name
   *   The name of the formatter for the current resource.
   *
   * @return FormatterInterface
   *   The formatter plugin to use.
   */
  public function negotiateFormatter($accept, $formatter_name = NULL);

  /**
   * Returns the plugins.
   *
   * @return FormatterPluginCollection
   */
  public function getPlugins();

  /**
   * Returns the plugin instance for the given instance ID.
   *
   * @return FormatterInterface
   */
  public function getPlugin($instance_id);

}

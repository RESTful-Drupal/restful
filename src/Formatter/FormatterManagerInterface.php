<?php

/**
 * @file
 * Contains \Drupal\restful\Formatter\FormatterManagerInterface
 */

namespace Drupal\restful\Formatter;

interface FormatterManagerInterface {

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

}

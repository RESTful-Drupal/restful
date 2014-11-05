<?php

/**
 * @file
 * Contains RestfulFormatterInterface.
 */

interface RestfulFormatterInterface {

  /**
   * Massages the raw data to create a structured array to pass to the renderer.
   *
   * @param array $data
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
}

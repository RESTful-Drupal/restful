<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\formatter\Formatter
 */

namespace Drupal\restful\Plugin\formatter;

use Drupal\Component\Plugin\PluginBase;

abstract class Formatter extends PluginBase implements FormatterInterface {

  /**
   * The resource handler containing more info about the request.
   *
   * @var \RestfulBase
   */
  protected $resource;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->resource = $configuration['resource'];
  }

  /**
   * {@inheritdoc}
   */
  public function format(array $data) {
    return $this->render($this->prepare($data));
  }

  /**
   * {@inheritdoc}
   */
  public function getContentTypeHeader() {
    // Default to the most generic content type.
    return 'application/hal+json; charset=utf-8';
  }

}

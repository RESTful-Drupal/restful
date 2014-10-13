<?php

/**
 * @file
 * Contains RestfulFormatterBase.
 */

abstract class RestfulFormatterBase extends \RestfulPluginBase implements \RestfulFormatterInterface {
  /**
   * The entity handler containing more info about the request.
   *
   * @var \RestfulBase
   */
  protected $handler;

  /**
   * Generic constructor.
   *
   * @param array $plugin
   *   The formatter plugin definition.
   * @param \RestfulBase $handler
   *   The restful handler that will call the output formatter.
   */
  public function __construct(array $plugin, $handler = NULL) {
    $this->setPlugin($plugin);
    $this->handler = $handler;
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
    // Default to the most general content type.
    return 'application/hal+json; charset=utf-8';
  }

}

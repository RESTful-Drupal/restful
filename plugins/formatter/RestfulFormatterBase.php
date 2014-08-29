<?php

/**
 * @file
 * Contains RestfulFormatterBase.
 */

abstract class RestfulFormatterBase implements \RestfulFormatterInterface {
  /**
   * The entity handler containing more info about the request.
   *
   * @var \RestfulEntityBase
   */
  protected $handler;

  /**
   * The plugin definition array.
   *
   * @var array
   */
  protected $plugin;

  /**
   * General constructor.
   */
  public function __construct(array $plugin, \RestfulEntityBase $handler) {
    $this->plugin = $plugin;
    $this->handler = $handler;
  }

  /**
   * {@inheritdoc}
   */
  public function format(array $data) {
    return $this->render($this->massage($data));
  }

  /**
   * {@inheritdoc}
   */
  public function getContentTypeHeader() {
    // Default to the most general & obvious content type.
    return 'text/html';
  }

}

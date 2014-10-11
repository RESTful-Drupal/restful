<?php

/**
 * @file
 * Contains \RestfulPluginBase.
 */

abstract class RestfulPluginBase implements \RestfulPluginInterface {

  /**
   * @var array
   *
   * The plugin definition array.
   */
  protected $plugin;

  /**
   * Class constructor.
   *
   * @param array $plugin
   *   The plugin definition array.
   */
  public function __construct(array $plugin) {
    $this->plugin = $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginInfo($key = NULL) {
    if (isset($key)) {
      return $this->isEmpty($key) ? NULL : $this->plugin[$key];
    }
    return $this->plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function isNull($key) {
    return !isset($this->plugin[$key]);
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty($key) {
    return $this->isNull($key) && $this->getPluginInfo($key);
  }

}

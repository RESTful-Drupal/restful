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
  public function getPluginKey($key = NULL) {
    return isset($this->plugin[$key]) ? $this->plugin[$key] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setPluginKey($key, $value) {
    $this->plugin[$key] = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugin() {
    return $this->plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function setPlugin($plugin) {
    $this->plugin = $plugin;
  }

}

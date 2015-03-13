<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\ConfigurablePluginTrait
 */

namespace Drupal\restful\Plugin;

trait ConfigurablePluginTrait {

  /**
   * Plugin instance configuration.
   *
   * @var array
   */
  protected $instanceConfiguration;

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    if (!isset($this->instanceConfiguration)) {
      $this->instanceConfiguration = $this->defaultConfiguration();
    }
    return $this->instanceConfiguration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->instanceConfiguration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return array();
  }



}

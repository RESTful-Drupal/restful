<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\DataInterpreter\PluginWrapper.
 */

namespace Drupal\restful\Plugin\resource\DataInterpreter;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

class PluginWrapper implements PluginWrapperInterface {

  /**
   * Plugin configuration.
   *
   * @var array
   */
  protected $pluginConfiguration = array();

  /**
   * Plugin definition.
   *
   * @var array
   */
  protected $pluginDefinition = array();

  /**
   * Plugin instance.
   *
   * @var PluginInspectionInterface
   */
  protected $plugin;

  /**
   * Constructs a PluginWrapper object.
   *
   * @param PluginInspectionInterface $plugin
   *   The plugin to wrap.
   */
  public function __construct(PluginInspectionInterface $plugin) {
    $this->plugin = $plugin;
    $this->pluginDefinition = $plugin->getPluginDefinition();
    // For configurable plugins, expose those properties as well.
    if ($plugin instanceof ConfigurablePluginInterface) {
      $this->pluginConfiguration = $plugin->getConfiguration();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function get($key) {
    // If there is a key by that name in the plugin configuration return it, if
    // not then check the plugin definition. If it cannot be found, return NULL.
    $value = isset($this->pluginConfiguration[$key]) ? $this->pluginConfiguration[$key] : NULL;
    return $value ? $value : (isset($this->pluginDefinition[$key]) ? $this->pluginDefinition[$key] : NULL);
  }

}

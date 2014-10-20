<?php

/**
 * @file
 * Contains \RestfulQueryVariable
 */

class RestfulCToolsPluginsDiscovery extends \RestfulDataProviderCToolsPlugins {

  /**
   * {@inheritdoc}
   */
  public function publicFieldsInfo() {
    return array(
      'name' => array(
        'property' => 'name',
      ),
      'description' => array(
        'property' => 'description',
      ),
      'resource' => array(
        'property' => 'resource',
      ),
      'major_version' => array(
        'property' => 'major_version',
      ),
      'minor_version' => array(
        'property' => 'minor_version',
      ),
      'self' => array(
        'callback' => array($this, 'getSelf'),
      ),
    );
  }

  /**
   * Overrides \RestfulDataProviderCToolsPlugins::getPlugins().
   *
   * Remove the discovery plugin(s) from the list.
   */
  public function getPlugins() {
    $plugins = parent::getPlugins();

    foreach (array_keys($plugins) as $plugin_name) {
      if (strpos($plugin_name, 'discovery__') === 0) {
        unset($plugins[$plugin_name]);
      }
    }
    return $plugins;
  }

  protected function getSelf($plugin) {
    return $this->getUrl(array(), $plugin['name']);
  }

}

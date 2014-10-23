<?php

/**
 * @file
 * Contains \RestfulCToolsPluginsDiscovery
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
        'property' => 'menu_item',
        'process_callbacks' => array(
          array($this, 'getSelf'),
        ),

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

    foreach ($plugins as $plugin_name => $plugin) {
      if (!$plugin['discoverable']) {
        unset($plugins[$plugin_name]);
      }
    }
    return $plugins;
  }

  protected function getSelf($url) {
    return url($url, array('absolute' => TRUE));
  }

}

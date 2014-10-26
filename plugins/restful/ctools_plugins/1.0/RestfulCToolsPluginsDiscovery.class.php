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
      'label' => array(
        'property' => 'label',
      ),
      'description' => array(
        'property' => 'description',
      ),
      'name' => array(
        'property' => 'name',
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

    foreach ($plugins as $plugin_name => $plugin) {
      if (!$plugin['discoverable']) {
        unset($plugins[$plugin_name]);
      }
    }

    $request = $this->getRequest();
    if (empty($request['all'])) {
      // Return only the last version of each resource.
      foreach ($plugins as $plugin_name => $plugin) {
        list($major_version, $minor_version) = static::getResourceLastVersion($plugin['resource']);

        if ($plugin['major_version'] != $major_version || $plugin['minor_version'] != $minor_version) {
          unset($plugins[$plugin_name]);
        }
      }
    }

    return $plugins;
  }

  /**
   * Returns the URL to the endpoint result.
   *
   * @param array $plugin
   *   The unprocessed plugin definition.
   *
   * @return string
   *   The RESTful endpoint.
   */
  protected function getSelf($plugin) {
    if ($plugin['menu_item']) {
      return url($plugin['menu_item'], array('absolute' => TRUE));
    }

    $base_path = variable_get('restful_hook_menu_base_path', 'api');
    return url($base_path . '/v' . $plugin['major_version'] . '.' . $plugin['minor_version'] . '/' . $plugin['resource'], array('absolute' => TRUE));
  }

}

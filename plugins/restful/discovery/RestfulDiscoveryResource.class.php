<?php

/**
 * @file
 * Contains RestfulDiscoveryResource.
 */

class RestfulDiscoveryResource extends \RestfulBase implements \RestfulDataProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function publicFieldsInfo() {
    return array(
      'resources' => array(
        'callback' => 'static::getResourceInfo',
      ),
    );
  }

  /**
   * Value callback; Return the discovery info.
   *
   * @return array
   */
  protected static function getResourceInfo() {
    $output = array();
    foreach (restful_get_restful_plugins() as $plugin) {
      // Remove the implementation specific keys.
      unset($plugin['class']);
      unset($plugin['hook_menu']);
      unset($plugin['module']);
      unset($plugin['get children']);
      unset($plugin['get child']);
      unset($plugin['path']);
      unset($plugin['file']);
      unset($plugin['plugin module']);
      unset($plugin['plugin type']);
      $plugin['render_cache'] = $plugin['render_cache']['render'];
      $plugin['autocomplete'] = $plugin['autocomplete']['enable'];
      $plugin['rate_limit'] = array_keys($plugin['rate_limit']);
      if (!empty($plugin['menu_item'])) {
        $plugin['uri'] = url($plugin['menu_item'], array('absolute' => TRUE));
      }
      unset($plugin['menu_item']);
      $output[$plugin['name']] = $plugin;
    }

    return $output;
  }

  /**
   * Overrides RestfulBase::access().
   *
   * Expose resource only to authenticated users.
   */
  public function access() {
    return user_access('access discovery information', $this->getAccount());
  }

  /**
   * {@inheritdoc}
   */
  public function index() {
    foreach ($this->getPublicFields() as $public_property => $info) {
      $value = NULL;

      if ($info['callback']) {
        $value = static::executeCallback($info['callback']);
      }

      if ($value && $info['process_callbacks']) {
        foreach ($info['process_callbacks'] as $process_callback) {
          $value = static::executeCallback($process_callback, array($value));
        }
      }

      $values[$public_property] = $value;
    }

    return $values;
  }

}

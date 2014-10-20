<?php

/**
 * @file
 * Contains \RestfulDataProviderEFQ
 */

abstract class RestfulDataProviderCToolsPlugins extends \RestfulBase implements \RestfulDataProviderCToolsPluginsInterface {

  /**
   * The module name.
   *
   * @var string
   */
  protected $module;

  /**
   * The type of the plugin.
   *
   * @var string
   */
  protected $type;

  protected $plugins = array();

  /**
   * @return string
   */
  public function getModule() {
    return $this->module;
  }

  /**
   * @return string
   */
  public function getType() {
    return $this->type;
  }

  /**
   * @return array
   */
  public function getPlugins() {
    if ($this->plugins) {
      return $this->plugins;
    }

    ctools_include('plugins');
    $this->plugins = ctools_get_plugins($this->getModule(), $this->getType());
    return $this->plugins;
  }



  /**
   * Constructs a RestfulDataProviderEFQ object.
   *
   * @param array $plugin
   *   Plugin definition.
   * @param RestfulAuthenticationManager $auth_manager
   *   (optional) Injected authentication manager.
   * @param DrupalCacheInterface $cache_controller
   *   (optional) Injected cache backend.
   */
  public function __construct(array $plugin, \RestfulAuthenticationManager $auth_manager = NULL, \DrupalCacheInterface $cache_controller = NULL) {
    parent::__construct($plugin, $auth_manager, $cache_controller);

    $options = $this->getPluginKey('data_provider_options');

    $this->module = $options['module'];
    $this->type = $options['type'];
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalCount() {
    ctools_include('plugins');
    return count($this->getPlugins());
  }

  public function index() {
    // TODO: Right now render cache only works for Entity based resources.
    $return = array();

    foreach (array_keys($this->getPlugins()) as $plugin_name) {
      $return[] = $this->view($plugin_name);
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function view($id) {
    ctools_include('plugins');
    $plugin = ctools_get_plugins($this->getModule(), $this->getType(), $id);

    // Loop over all the defined public fields.
    foreach ($this->getPublicFields() as $public_field_name => $info) {
      $value = NULL;
      // If there is a callback defined execute it instead of a direct mapping.
      if ($info['callback']) {
        $value = static::executeCallback($info['callback'], array($plugin));
      }
      // Map row names to public properties.
      elseif ($info['property']) {
        $value = $plugin[$info['property']];
      }

      // Execute the process callbacks.
      if ($value && $info['process_callbacks']) {
        foreach ($info['process_callbacks'] as $process_callback) {
          $value = static::executeCallback($process_callback, array($value));
        }
      }

      $output[$public_field_name] = $value;
    }

    return $output;
  }

}

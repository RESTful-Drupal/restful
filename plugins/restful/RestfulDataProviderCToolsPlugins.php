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

  /**
   * The loaded plugins.
   *
   * @var array
   */
  protected $plugins = array();

  /**
   * Get the module name.
   *
   * @return string
   */
  public function getModule() {
    return $this->module;
  }

  /**
   * Get the plugin type.
   *
   * @return string
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Return the plugins.
   *
   * @return array
   */
  public function getPlugins() {
    if ($this->plugins) {
      return $this->plugins;
    }

    $this->plugins = ctools_get_plugins($this->getModule(), $this->getType());
    return $this->plugins;
  }

  public function getPluginsSortedAndFiltered() {
    $sorts = $this->parseRequestForListSort();
    $plugins = $this->getPlugins();
    $public_fields = $this->getPublicFields();

    foreach ($this->parseRequestForListFilter() as $filter) {
      foreach ($plugins as $plugin_name => $plugin) {
        $property = $public_fields[$filter['public_field']]['property'];

        if (empty($plugin[$property])) {
          // Property doesn't exist on the plugin, so filter it out.
          unset($plugins[$plugin_name]);
        }

        if (!$this->evaluateExpression($filter['value'], $plugin[$property], $filter['operator'])) {
          // Property doesn't match the filter.
          unset($plugins[$plugin_name]);
        }
      }
    }

    foreach ($this->parseRequestForListSort() as $sort) {
      // @todo
    }

    return $plugins;

  }

  /**
   * Evaluate a simple expression.
   *
   * @param $value1
   *   The first value.
   * @param $value2
   *   The second value.
   * @param $operator
   *   The operator.
   *
   * @return bool
   *   TRUE or FALSE based on the evaluated expression.
   *
   * @throws RestfulBadRequestException
   */
  protected function evaluateExpression($value1, $value2, $operator) {
    switch($operator) {
      case '=':
        return $value1 == $value2;

      case '<':
        return $value1 < $value2;

      case '>':
        return $value1 > $value2;

      case '>=':
        return $value1 >= $value2;

      case '<=':
        return $value1 <= $value2;

      case '<>':
        return $value1 != $value2;

      case 'BETWEEN':
        // The passed second value is an array.
        return $value1 >= $value2[0] && $value1 >= $value2[1];

      throw new \RestfulBadRequestException('Operator @operator is not allowed for filtering on this resource.', array('@operator' => $operator));
    }


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

    ctools_include('plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalCount() {
    return count($this->getPluginsSortedAndFiltered());
  }

  public function index() {
    // TODO: Right now render cache only works for Entity based resources.
    $return = array();

    foreach (array_keys($this->getPluginsSortedAndFiltered()) as $plugin_name) {
      $return[] = $this->view($plugin_name);
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function view($id) {
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

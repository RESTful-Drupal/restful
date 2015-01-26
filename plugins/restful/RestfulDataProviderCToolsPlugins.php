<?php

/**
 * @file
 * Contains \RestfulDataProviderCToolsPlugins
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

  /**
   * Gets the plugins filtered and sorted by the request.
   *
   * @return array
   *   Array of plugins.
   */
  public function getPluginsSortedAndFiltered() {
    $plugins = $this->getPlugins();
    $public_fields = $this->getPublicFields();

    foreach ($this->parseRequestForListFilter() as $filter) {
      foreach ($plugins as $plugin_name => $plugin) {
        // Initialize to TRUE for AND and FALSE for OR (neutral value).
        $match = $filter['conjunction'] == 'AND';
        for ($index = 0; $index < count($filter['value']); $index++) {
          $property = $public_fields[$filter['public_field']]['property'];

          if (empty($plugin[$property])) {
            // Property doesn't exist on the plugin, so filter it out.
            unset($plugins[$plugin_name]);
          }

          if ($filter['conjunction'] == 'OR') {
            $match = $match || $this->evaluateExpression($plugin[$property], $filter['value'][$index], $filter['operator'][$index]);
            if ($match) {
              break;
            }
          }
          else {
            $match = $match && $this->evaluateExpression($plugin[$property], $filter['value'][$index], $filter['operator'][$index]);
            if (!$match) {
              break;
            }
          }
        }
        if (!$match) {
          // Property doesn't match the filter.
          unset($plugins[$plugin_name]);
        }
      }
    }


    if ($this->parseRequestForListSort()) {
      uasort($plugins, array($this, 'sortMultiCompare'));
    }

    return $plugins;
  }

  /**
   * Overrides \RestfulBase::isValidConjuctionForFilter().
   */
  protected static function isValidConjunctionForFilter($conjunction) {
    $allowed_conjunctions = array(
      'AND',
      'OR',
    );

    if (!in_array(strtoupper($conjunction), $allowed_conjunctions)) {
      throw new \RestfulBadRequestException(format_string('Conjunction "@conjunction" is not allowed for filtering on this resource. Allowed conjunctions are: !allowed', array(
        '@conjunction' => $conjunction,
        '!allowed' => implode(', ', $allowed_conjunctions),
      )));
    }
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
      case '!=':
        return $value1 != $value2;

      case 'IN':
        return in_array($value1, $value2);

      case 'BETWEEN':
        return $value1 >= $value2[0] && $value1 >= $value2[1];
    }
  }

  /**
   * Sort plugins by multiple criteria.
   *
   * @param $value1
   *   The first value.
   * @param $value2
   *   The second value.
   *
   * @return int
   *   The values expected by uasort() function.
   *
   * @link http://stackoverflow.com/a/13673568/750039
   */
  protected function sortMultiCompare($value1, $value2) {
    $sorts = $this->parseRequestForListSort();
    foreach ($sorts as $key => $order){
      if ($value1[$key] == $value2[$key]) {
        continue;
      }

      return ($order == 'DESC' ? -1 : 1) * strcmp($value1[$key], $value2[$key]);
    }

    return 0;
  }

  /**
   * Constructs a RestfulDataProviderCToolsPlugins object.
   *
   * @param array $plugin
   *   Plugin definition.
   * @param RestfulAuthenticationManager $auth_manager
   *   (optional) Injected authentication manager.
   * @param DrupalCacheInterface $cache_controller
   *   (optional) Injected cache backend.
   * @param string $language
   *   (optional) The language to return items in.
   */
  public function __construct(array $plugin, \RestfulAuthenticationManager $auth_manager = NULL, \DrupalCacheInterface $cache_controller = NULL, $language = NULL) {
    parent::__construct($plugin, $auth_manager, $cache_controller, $language);

    // Validate keys exist in the plugin's "data provider options".
    $required_keys = array(
      'module',
      'type',
    );
    $options = $this->processDataProviderOptions($required_keys);

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
    $return = array();

    foreach (array_keys($this->getPluginsSortedAndFiltered()) as $plugin_name) {
      $return[] = $this->view($plugin_name);
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   *
   * @todo: We should generalize this, as it's repeated often.
   */
  public function view($id) {
    $cache_id = array(
      'md' => $this->getModule(),
      'tp' => $this->getType(),
      'id' => $id,
    );
    $cached_data = $this->getRenderedCache($cache_id);
    if (!empty($cached_data->data)) {
      return $cached_data->data;
    }

    if (!$plugin = ctools_get_plugins($this->getModule(), $this->getType(), $id)) {
      // Since the discovery resource sits under 'api/' it will pick up all
      // invalid paths like 'api/invalid'. If it is not a valid plugin then
      // return a 404.
      throw new \RestfulNotFoundException('Invalid URL path.');
    }

    // Loop over all the defined public fields.
    foreach ($this->getPublicFields() as $public_field_name => $info) {
      $value = NULL;

      if ($info['create_or_update_passthrough']) {
        // The public field is a dummy one, meant only for passing data upon
        // create or update.
        continue;
      }

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

    $this->setRenderedCache($output, $cache_id);
    return $output;
  }
}

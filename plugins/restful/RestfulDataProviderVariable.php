<?php

/**
 * @file
 * Contains \RestfulDataProviderDbQuery
 */

abstract class RestfulDataProviderVariable extends \RestfulBase implements \RestfulDataProviderInterface {

  /**
   * Constructs a RestfulDataProviderVariable object.
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
  }

  /**
   * {@inheritdoc}
   */
  public static function controllersInfo() {
    return array(
      '' => array(
        // GET returns a list of entities.
        \RestfulInterface::GET => 'index',
        \RestfulInterface::HEAD => 'index',
        // POST
        \RestfulInterface::POST => 'setVariable',
      ),
      // We don't know what the ID looks like, assume that everything is the ID.
      '^.*$' => array(
        \RestfulInterface::GET => 'viewVariables',
        \RestfulInterface::HEAD => 'viewVariables',
        \RestfulInterface::PUT => 'setVariable',
        \RestfulInterface::PATCH => 'setVariable',
        \RestfulInterface::DELETE => 'remove',
      ),
    );
  }

  /**
   * Defines default sort for variable names.
   *
   * By default, the array of variables returned by Drupal is already sorted
   * in ascending order by variable name.
   *
   * @return array
   *   Array keyed by the sort field, with the order ('ASC' or 'DESC') as value.
   */
  public function defaultSortInfo() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getVariablesForList() {
    global $conf;
    $variables = $conf;

    $this->applyListSort($variables);
    $this->applyListPagination($variables);

    return $variables;
  }

  /**
   * Sort the list of variables by name.
   *
   * @param string $a
   *   The first variable name for the comparison.
   * @param string $b
   *   The second variable name for the comparison.
   */
  public function nameSort($a, $b) {
    return strcasecmp($a, $b);
  }

  /**
   * Sort the list of variables by value.
   *
   * If you are comparing variables with the same value, note that the PHP
   * sorting algorithm is not stable, and their order may be changed.
   *
   * @param mixed $a
   *   The first data structure for the comparison.
   * @param mixed $b
   *   The second data structure for the comparison.
   */
  public function valueSort($a, $b) {
    return;
  }

  /**
   * Sort the list of variables.
   *
   * This data provider does not handle compound sorts; the last sort defined
   * will be the one to take effect.
   *
   * @param array $variables
   *   An array keyed by variable name, valued by unserialized variable value.
   *
   * @throws \RestfulBadRequestException
   */
  protected function applyListSort(array &$variables) {
    $public_fields = $this->getPublicFields();

    // Get the sorting options from the request object.
    $sorts = $this->parseRequestForListSort();

    $sorts = $sorts ? $sorts : $this->defaultSortInfo();

    foreach ($sorts as $public_field_name => $direction) {
      if (isset($public_fields[$public_field_name]['property'])) {
        $property_name = $public_fields[$public_field_name]['property'];
        if ($property_name == 'name') {
          uksort($variables, array(__CLASS__, 'nameSort'));
        }
        elseif ($property_name == 'value') {
          uasort($variables, array(__CLASS__, 'valueSort'));
        }
      }
      if ($direction == 'DESC') {
        $variables = array_reverse($variables, TRUE);
      }
    }

    return $variables;
  }

  /**
   * Set correct page for the index within the array of variables.
   *
   * Determine the page that should be seen. Page 1 is actually index 0.
   *
   * @param array $variables
   *   An array keyed by variable name, valued by unserialized variable value.
   *
   * @throws \RestfulBadRequestException
   */
  protected function applyListPagination(array &$variables) {
    list($offset, $range) = $this->parseRequestForListPagination();
    $variables = array_slice($variables, $offset, $range);
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalCount() {
    return array('restful_count' => count($this->getVariablesForList()));
  }

  /**
   * {@inheritdoc}
   */
  public function index() {
    $variables = $this->getVariablesForList();
    $return = array();
    foreach ($variables as $variable => $value) {
      $return[] = $this->view($variable);
    }

    return $return;
  }

  /**
   * Helper function to feed an array into viewMultiple().
   *
   * @param string $name_string
   *  A string of variable names, separated by commas.
   */
  public function viewVariables($name_string) {
    $names = array_unique(array_filter(explode(',', $name_string)));
    return $this->viewMultiple($names);
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $names) {
    if (empty($names)) {
      return array();
    }
    $variables = array_keys($this->getVariablesForList());
    $output = array();

    // Build output according to filtered/sorted list of variables.
    foreach ($variables as $variable) {
      if (in_array($variable, $names)) {
        $output[] = $this->view($variable);
      }
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function view($name) {
    $cache_id = array(
      'tb' => 'variable',
      'id' => $name,
    );
    $cached_data = $this->getRenderedCache($cache_id);
    if (!empty($cached_data->data)) {
      return $cached_data->data;
    }

    $return = array();
    foreach ($this->getPublicFields() as $public_field_name => $info) {
      if ($info['property'] == 'name') {
        $public_field_value = $name;
      }
      elseif ($info['property'] == 'value') {
        $public_field_value = variable_get($name);
      }
      else {
        continue;
      }

      // Modify the public field value using a callback, if supplied.
      $public_field_value = $info['callback'] ? static::executeCallback($info['callback'], array($value)) : $public_field_value;

      // Modify the public field value using a process callback, if supplied.
      if ($public_field_value && $info['process_callbacks']) {
        foreach ($info['process_callbacks'] as $process_callback) {
          $public_field_value = static::executeCallback($process_callback, array($public_field_value));
        }
      }

      $return[$public_field_name] = $public_field_value;
    }

    $this->setRenderedCache($return, $cache_id);

    return $return;
  }

  /**
   * Alias for $this->setVariable().
   *
   * @param string $name
   *  The name of the variable to set a value for.
   */
  public function update($name, $full_update = TRUE) {
    $this->setVariable($name);
  }

  /**
   * Sets a variable value.
   *
   * This method is used for both 'create' and 'update' contexts.
   *
   * @param string $name
   *  The name of the variable to set a value for.
   *
   * @todo: get $name from the request: make it optional for create()/update().
   */
  public function setVariable() {
    $request = $this->getRequest();
    static::cleanRequest($request);

    // Retrieve the name and value from the request, if present.
    $public_fields = $this->getPublicFields();
    $value = '';
    foreach ($public_fields as $public_property => $info) {
      if ($info['property'] == 'name' && isset($request[$public_property])) {
        $name = $request[$public_property];
      }
      elseif ($info['property'] == 'value' && isset($request[$public_property])) {
        $value = $request[$public_property];
      }
    }
    if (isset($name)) {
      variable_set($name, $value);

      // Clear the rendered cache before calling the view method.
      $this->clearRenderedCache(array(
        'tb' => 'variable',
        'id' => $name,
      ));

      return $this->view($name);
    }
    else {
      throw new Exception('No name property supplied');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function remove($name) {
    variable_del($name);
    $this->setHttpHeaders('Status', 204);
  }
}

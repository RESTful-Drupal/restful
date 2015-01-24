<?php

/**
 * @file
 * Contains \RestfulDataProviderDbQuery
 */

abstract class RestfulDataProviderVariable extends \RestfulBase implements \RestfulDataProviderInterface {
  /**
   * Defines default sort for variable names.
   *
   * By default, the array of variables returned by Drupal is already sorted
   * by variable name in ascending order, so we are returning a default sort
   * here just for clarity.
   *
   * @return array
   *   Array keyed by the sort field, with the order ('ASC' or 'DESC') as value.
   */
  public function defaultSortInfo() {
    return array('name' => 'ASC');
  }

  /**
   * {@inheritdoc}
   */
  public function getVariablesForList() {
    // Map name and value to an indexed array structure.
    foreach ($GLOBALS['conf'] as $variable_name => $variable_value) {
      $variables[] = array(
        'name' => $variable_name,
        'value' => $variable_value,
      );
    }

    // Apply pagination and sorting.
    $this->applyListSort($variables);
    $this->applyListPagination($variables);

    return $variables;
  }

  /**
   * Sort the list of variables.
   *
   * This data provider does not handle compound sorts; the last sort defined
   * will be the one to take effect.
   *
   * @param array $variables
   *   An indexed array containing elements that represent each variable, each
   *   containing a name and a value.
   */
  protected function applyListSort(array &$variables) {
    $public_fields = $this->getPublicFields();

    // Get the sorting options from the request object.
    $sorts = $this->parseRequestForListSort();

    $sorts = $sorts ? $sorts : $this->defaultSortInfo();

    foreach ($sorts as $public_field_name => $direction) {
      if (isset($public_fields[$public_field_name]['property'])) {
        $property_name = $public_fields[$public_field_name]['property'];
        // Only sort by name if it's different than Drupal's default.
        if ($property_name == 'name' && $direction == 'DESC') {
          $variables = array_reverse($variables);
        }
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
   * Returns the total count of all variables.
   */
  public function getTotalCount() {
    return count($GLOBALS['conf']);
  }

  /**
   * {@inheritdoc}
   */
  public function index() {
    $variables = $this->getVariablesForList();
    $return = array();
    foreach ($variables as $variable) {
      $return[] = $this->mapVariableToPublicFields($variable);
    }

    return $return;
  }

  /**
   * View a variable or multiple variables.
   *
   * @param string $name_string
   *  A string of variable names, separated by commas.
   */
  public function view($name_string) {
    $names = array_unique(array_filter(explode(',', $name_string)));

    $output = array();
    foreach ($names as $name) {
      $output[] = $this->viewVariable($name);
    }

    return $output;
  }

  /**
   * View a single variable.
   *
   * @param string $name_string
   *  A string of variable names, separated by commas.
   */
  public function viewVariable($name) {
    // Caching is done on the individual variables.
    $cache_id = array(
      'tb' => 'variable',
      'id' => $name,
    );
    $cached_data = $this->getRenderedCache($cache_id);
    if (!empty($cached_data->data)) {
      return $cached_data->data;
    }

    $variable['name'] = $name;
    $variable['value'] = variable_get($name);

    $return = $this->mapVariableToPublicFields($variable);
    $this->setRenderedCache($return, $cache_id);

    return $return;
  }

  /**
   * Alias for $this->variableSet().
   *
   * @param string $name
   *  The name of the variable to set a value for.
   */
  public function replace($name) {
    return $this->variableSet($name, TRUE);
  }

  /**
   * Alias for $this->variableSet().
   *
   * The data structures of variable values are all different, therefore it's
   * impossible to do a partial update in a generic way.
   *
   * @param string $name
   *  The name of the variable to set a value for.
   * @param boolean $full_replace
   *  Completely replace variable values with supplied values.
   */
  public function update($name, $full_replace = FALSE) {
    return $this->variableSet($name, FALSE);
  }

  /**
   * Alias for $this->variableSet().
   */
  public function create() {
    return $this->variableSet();
  }

  /**
   * Sets a variable value.
   *
   * If no variable name is provided in the function call, such as for POST
   * requests, then this method will get the name from the request body.
   *
   * @param string $name
   *  The name of the variable to set a value for.
   * @param boolean $full_replace
   *  Completely replace variable values with supplied values.
   */
  public function variableSet($name = NULL, $full_replace = TRUE) {
    $request = $this->getRequest();
    static::cleanRequest($request);

    // Retrieve the name and value from the request, if present.
    $public_fields = $this->getPublicFields();

    // Set initial empty value for replace and create contexts.
    if ($full_replace) {
      $value = '';
    }

    foreach ($public_fields as $public_property => $info) {
      // Set the name from the request if it wasn't provided.
      if ($info['property'] == 'name'
        && isset($request[$public_property])
        && empty($name)) {
          $name = $request[$public_property];
      }
      // Overwrite empty $value with value from the request, if given.
      if ($info['property'] == 'value' && isset($request[$public_property])) {
        $value = $request[$public_property];
      }
    }

    if (isset($name)) {
      if (isset($value)) {
        variable_set($name, $value);

        // Clear the rendered cache before calling the view method.
        $this->clearRenderedCache(array(
          'tb' => 'variable',
          'id' => $name,
        ));
      }
      // Update contexts could have no value set; if so, do nothing.

      return $this->view($name);
    }
    else {
      // We are in a create context with no name supplied.
      throw new RestfulBadRequestException('No name property supplied');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function remove($name) {
    variable_del($name);
    $this->setHttpHeaders('Status', 204);
  }

  /**
   * Maps variable names and values to public fields.
   *
   * @param array $variable
   *   An array containing the name and value of the variable.
   */
  public function mapVariableToPublicFields($variable) {
    foreach ($this->getPublicFields() as $public_field_name => $info) {
      if (!empty($info['property'])) {
        if (isset($info['property']) && $info['property'] == 'name') {
          $public_field_value = $variable['name'];
        }
        elseif (isset($info['property']) && $info['property'] == 'value') {
          $public_field_value = $variable['value'];
        }
        else {
          throw new RestfulBadRequestException("The only possible properties for the variable resource are 'name' and 'value'.");
        }
      }
      // If no property is supplied, execute a callback, if given.
      elseif ($info['callback']) {
        $public_field_value = static::executeCallback($info['callback'], array($name));
      }

      // Modify the public field value using a process callback, if supplied.
      if ($public_field_value && $info['process_callbacks']) {
        foreach ($info['process_callbacks'] as $process_callback) {
          $public_field_value = static::executeCallback($process_callback, array($public_field_value));
        }
      }

      $return[$public_field_name] = $public_field_value;
    }

    return $return;
  }
}

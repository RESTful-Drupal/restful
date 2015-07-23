<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\DataProvider\DataProviderPlug.
 */

namespace Drupal\restful\Plugin\resource\DataProvider;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\restful\Exception\BadRequestException;
use Drupal\restful\Exception\NotFoundException;
use Drupal\restful\Exception\NotImplementedException;
use Drupal\restful\Exception\UnauthorizedException;
use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\DataInterpreter\DataInterpreterPlug;
use Drupal\restful\Plugin\resource\DataInterpreter\PluginWrapper;
use Drupal\restful\Plugin\resource\Field\ResourceFieldCollectionInterface;
use Drupal\restful\Plugin\resource\Field\ResourceFieldEntityInterface;
use Drupal\restful\Plugin\resource\ResourceInterface;

class DataProviderPlug extends DataProvider implements DataProviderInterface {
  /**
   * @inheritDoc
   */
  public function __construct(RequestInterface $request, ResourceFieldCollectionInterface $field_definitions, $account, $resource_path = NULL, array $options = array(), $langcode = NULL) {
    parent::__construct($request, $field_definitions, $account, $resource_path, $options, $langcode);
    if (empty($this->options['urlParams'])) {
      $this->options['urlParams'] = array(
        'filter' => TRUE,
        'sort' => TRUE,
        'fields' => TRUE,
      );
    }
  }


  /**
   * {@inheritdoc}
   */
  public function count() {
    return count($this->getIndexIds());
  }

  /**
   * {@inheritdoc}
   */
  public function create($object) {
    throw new NotImplementedException('You cannot create plugins through the API.');
  }

  /**
   * {@inheritdoc}
   */
  public function view($identifier) {
    $values = array();
    $resource_manager = restful()->getResourceManager();
    try {
      $plugin = $resource_manager->getPlugin($identifier);
    }
    catch (UnauthorizedException $e) {
      return NULL;
    }
    catch (PluginNotFoundException $e) {
      throw new NotFoundException('Invalid URL path.');
    }
    $interpreter = new DataInterpreterPlug($this->getAccount(), new PluginWrapper($plugin));

    $input = $this->getRequest()->getParsedInput();
    $limit_fields = !empty($input['fields']) ? explode(',', $input['fields']) : array();

    foreach ($this->fieldDefinitions as $resource_field_name => $resource_field) {
      /* @var ResourceFieldEntityInterface $resource_field */
      if ($limit_fields && !in_array($resource_field_name, $limit_fields)) {
        // Limit fields doesn't include this property.
        continue;
      }

      $value = NULL;

      if (!$this->methodAccess($resource_field) || !$resource_field->access('view', $interpreter)) {
        // The field does not apply to the current method or has denied
        // access.
        continue;
      }

      $value = $resource_field->value($interpreter);

      $value = $this->processCallbacks($value, $resource_field);

      $values[$resource_field_name] = $value;
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $identifiers) {
    $output = array();
    foreach ($identifiers as $identifier) {
      if ($values = $this->view($identifier)) {
        $output[] = $values;
      }
    }
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function update($identifier, $object, $replace = FALSE) {
    $disabled_plugins = variable_get('restful_disabled_plugins', array());
    if ($object['enable']) {
      $disabled_plugins[$identifier] = FALSE;
    }
    variable_set('restful_disabled_plugins', $disabled_plugins);
  }

  /**
   * {@inheritdoc}
   */
  public function remove($identifier) {
    $disabled_plugins = variable_get('restful_disabled_plugins', array());
    $disabled_plugins[$identifier] = TRUE;
    variable_set('restful_disabled_plugins', $disabled_plugins);
  }

  /**
   * {@inheritdoc}
   */
  public function getIndexIds() {
    // Return all of the instance IDs, even for disabled resources.
    $plugins = restful()
      ->getResourceManager()
      ->getPlugins(TRUE);

    $output = $plugins->getIterator()->getArrayCopy();

    // Apply filters.
    $output = $this->applyFilters($output);
    $output = $this->applySort($output);
    return array_keys($output);
  }

  /**
   * Removes plugins from the list based on the request options.
   *
   * @param ResourceInterface[] $plugins
   *   The array of resource plugins keyed by instance ID.
   *
   * @return ResourceInterface[]
   *   The same array minus the filtered plugins.
   */
  protected function applyFilters(array $plugins) {
    $resource_manager = restful()->getResourceManager();
    $filters = $this->parseRequestForListFilter();
    // If the 'all' option is not present, then add a filters to retrieve only
    // the last resource.
    $input = $this->getRequest()->getParsedInput();
    $all = !empty($input['all']);
    // Apply the filter to the list of plugins.
    foreach ($plugins as $instance_id => $plugin) {
      if (!$all) {
        // Remove the plugin if it's not the latest version.
        $version = $plugin->getVersion();
        list($last_major, $last_minor) = $resource_manager->getResourceLastVersion($plugin->getResourceMachineName());
        if ($version['major'] != $last_major || $version['minor'] != $last_minor) {
          // We don't add the major and minor versions to filters because we
          // cannot depend on the presence of the versions as public fields.
          unset($plugins[$instance_id]);
          continue;
        }
      }
      // If the discovery is turned off for the resource, unset it.
      $definition = $plugin->getPluginDefinition();
      if (!$definition['discoverable']) {
        unset($plugins[$instance_id]);
        continue;
      }
      foreach ($filters as $filter) {
        $interpreter = new DataInterpreterPlug($this->getAccount(), new PluginWrapper($plugin));
        // Initialize to TRUE for AND and FALSE for OR (neutral value).
        $match = $filter['conjunction'] == 'AND';
        for ($index = 0; $index < count($filter['value']); $index++) {
          $property = $this->fieldDefinitions->get($filter['public_field'])->getProperty();

          $plugin_value = $interpreter->getWrapper()->get($property);
          if (is_null($plugin_value)) {
            // Property doesn't exist on the plugin, so filter it out.
            unset($plugins[$instance_id]);
            continue;
          }

          if ($filter['conjunction'] == 'OR') {
            $match = $match || $this->evaluateExpression($plugin_value, $filter['value'][$index], $filter['operator'][$index]);
            if ($match) {
              break;
            }
          }
          else {
            $match = $match && $this->evaluateExpression($plugin_value, $filter['value'][$index], $filter['operator'][$index]);
            if (!$match) {
              break;
            }
          }
        }
        if (!$match) {
          // Property doesn't match the filter.
          unset($plugins[$instance_id]);
        }
      }
    }
    return $plugins;
  }

  /**
   * Sorts plugins on the list based on the request options.
   *
   * @param ResourceInterface[] $plugins
   *   The array of resource plugins keyed by instance ID.
   *
   * @return ResourceInterface[]
   *   The sorted array.
   */
  protected function applySort(array $plugins) {
    if ($sorts = $this->parseRequestForListSort()) {
      uasort($plugins, function ($plugin1, $plugin2) use ($sorts) {
        $interpreter1 = new DataInterpreterPlug($this->getAccount(), new PluginWrapper($plugin1));
        $interpreter2 = new DataInterpreterPlug($this->getAccount(), new PluginWrapper($plugin2));
        foreach ($sorts as $key => $order) {
          $property = $this->fieldDefinitions->get($key)->getProperty();
          $value1 = $interpreter1->getWrapper()->get($property);
          $value2 = $interpreter2->getWrapper()->get($property);
          if ($value1 == $value2) {
            continue;
          }

          return ($order == 'DESC' ? -1 : 1) * strcmp($value1, $value2);
        }

        return 0;
      });
    }
    return $plugins;
  }

  /**
   * Evaluate a simple expression.
   *
   * @param mixed $value1
   *   The first value.
   * @param mixed $value2
   *   The second value.
   * @param string $operator
   *   The operator.
   *
   * @return bool
   *   TRUE or FALSE based on the evaluated expression.
   *
   * @throws BadRequestException
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
    return FALSE;
  }

}

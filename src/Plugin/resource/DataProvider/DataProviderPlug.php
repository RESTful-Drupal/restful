<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\DataProvider\DataProviderPlug.
 */

namespace Drupal\restful\Plugin\resource\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\restful\Exception\NotFoundException;
use Drupal\restful\Exception\NotImplementedException;
use Drupal\restful\Exception\InaccessibleRecordException;
use Drupal\restful\Exception\UnauthorizedException;
use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\DataInterpreter\DataInterpreterPlug;
use Drupal\restful\Plugin\resource\DataInterpreter\PluginWrapper;
use Drupal\restful\Plugin\resource\Field\ResourceFieldCollectionInterface;

/**
 * Class DataProviderPlug.
 *
 * @package Drupal\restful\Plugin\resource\DataProvider
 */
class DataProviderPlug extends DataProvider implements DataProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(RequestInterface $request, ResourceFieldCollectionInterface $field_definitions, $account, $plugin_id, $resource_path = NULL, array $options = array(), $langcode = NULL) {
    parent::__construct($request, $field_definitions, $account, $plugin_id, $resource_path, $options, $langcode);
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
    $resource_field_collection = $this->initResourceFieldCollection($identifier);

    $input = $this->getRequest()->getParsedInput();
    $limit_fields = !empty($input['fields']) ? explode(',', $input['fields']) : array();

    foreach ($this->fieldDefinitions as $resource_field_name => $resource_field) {
      /* @var \Drupal\restful\Plugin\resource\Field\ResourceFieldInterface $resource_field */

      if ($limit_fields && !in_array($resource_field_name, $limit_fields)) {
        // Limit fields doesn't include this property.
        continue;
      }

      if (!$this->methodAccess($resource_field) || !$resource_field->access('view', $resource_field_collection->getInterpreter())) {
        // The field does not apply to the current method or has denied
        // access.
        continue;
      }

      $resource_field_collection->set($resource_field->id(), $resource_field);
    }
    return $resource_field_collection;
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $identifiers) {
    $return = array();
    foreach ($identifiers as $identifier) {
      try {
        $row = $this->view($identifier);
      }
      catch (InaccessibleRecordException $e) {
        $row = NULL;
      }
      $return[] = $row;
    }

    return array_values(array_filter($return));
  }

  /**
   * {@inheritdoc}
   */
  public function update($identifier, $object, $replace = FALSE) {
    // TODO: Document how to enable/disable resources using the API.
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
    // TODO: Document how to enable/disable resources using the API.
    $disabled_plugins = variable_get('restful_disabled_plugins', array());
    $disabled_plugins[$identifier] = TRUE;
    variable_set('restful_disabled_plugins', $disabled_plugins);
  }

  /**
   * {@inheritdoc}
   */
  public function getIndexIds() {
    // Return all of the instance IDs.
    $plugins = restful()
      ->getResourceManager()
      ->getPlugins();

    $output = $plugins->getIterator()->getArrayCopy();

    // Apply filters.
    $output = $this->applyFilters($output);
    $output = $this->applySort($output);
    return array_keys($output);
  }

  /**
   * Removes plugins from the list based on the request options.
   *
   * @param \Drupal\restful\Plugin\resource\ResourceInterface[] $plugins
   *   The array of resource plugins keyed by instance ID.
   *
   * @return \Drupal\restful\Plugin\resource\ResourceInterface[]
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

      // A filter on a result needs the ResourceFieldCollection representing the
      // result to return.
      $interpreter = new DataInterpreterPlug($this->getAccount(), new PluginWrapper($plugin));
      $this->fieldDefinitions->setInterpreter($interpreter);
      foreach ($filters as $filter) {
        if (!$this->fieldDefinitions->evalFilter($filter)) {
          unset($plugins[$instance_id]);
        }
      }
    }
    $this->fieldDefinitions->setInterpreter(NULL);
    return $plugins;
  }

  /**
   * Sorts plugins on the list based on the request options.
   *
   * @param \Drupal\restful\Plugin\resource\ResourceInterface[] $plugins
   *   The array of resource plugins keyed by instance ID.
   *
   * @return \Drupal\restful\Plugin\resource\ResourceInterface[]
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
   * {@inheritdoc}
   */
  protected function initDataInterpreter($identifier) {
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
    // If the plugin is not discoverable throw an access denied exception.
    $definition = $plugin->getPluginDefinition();
    if (empty($definition['discoverable'])) {
      throw new InaccessibleRecordException(sprintf('The plugin %s is not discoverable.', $plugin->getResourceName()));
    }
    return new DataInterpreterPlug($this->getAccount(), new PluginWrapper($plugin));
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheFragments($identifier) {
    // If we are trying to get the context for multiple ids, join them.
    if (is_array($identifier)) {
      $identifier = implode(',', $identifier);
    }
    $fragments = new ArrayCollection(array(
      'resource' => $identifier,
    ));
    $options = $this->getOptions();
    switch ($options['renderCache']['granularity']) {
      case DRUPAL_CACHE_PER_USER:
        if ($uid = $this->getAccount()->uid) {
          $fragments->set('user_id', (int) $uid);
        }
        break;
      case DRUPAL_CACHE_PER_ROLE:
        $fragments->set('user_role', implode(',', $this->getAccount()->roles));
        break;
    }
    return $fragments;
  }


}

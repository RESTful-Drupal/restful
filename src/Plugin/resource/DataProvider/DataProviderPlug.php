<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\DataProvider\DataProviderPlug.
 */

namespace Drupal\restful\Plugin\resource\DataProvider;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\restful\Exception\NotFoundException;
use Drupal\restful\Exception\NotImplementedException;
use Drupal\restful\Exception\UnauthorizedException;
use Drupal\restful\Plugin\resource\DataInterpreter\DataInterpreterPlug;
use Drupal\restful\Plugin\resource\DataInterpreter\PluginWrapper;
use Drupal\restful\Plugin\resource\Field\ResourceFieldEntityInterface;

class DataProviderPlug extends DataProvider implements DataProviderInterface {

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
    return $this->viewMultiple(array($identifier));
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $identifiers) {
    $output = array();
    $resource_manager = restful()->getResourceManager();
    foreach ($identifiers as $identifier) {
      $values = array();
      try {
        $plugin = $resource_manager->getPlugin($identifier);
      }
      catch (UnauthorizedException $e) {
        continue;
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

      $output[] = $values;
    }
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function update($identifier, $object, $replace = FALSE) {
    throw new NotImplementedException('You cannot create plugins through the API.');
  }

  /**
   * {@inheritdoc}
   */
  public function remove($identifier) {
    $disabled_plugins = variable_get('restful_disabled_plugins', array());
    $disabled_plugins[] = $identifier;
    variable_set($disabled_plugins, 'restful_disabled_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getIndexIds() {
    // Return all of the instance IDs, even for disabled resources.
    $ids = array_values(restful()
      ->getResourceManager()
      ->getPlugins()
      ->getInstanceIds());
    sort($ids);
    return $ids;
  }

}

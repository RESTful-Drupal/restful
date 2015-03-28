<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\ResourceEntity.
 */

namespace Drupal\restful\Plugin\resource;

use Drupal\restful\Exception\InternalServerErrorException;
use Drupal\restful\Exception\ServerConfigurationException;
use Drupal\restful\Plugin\resource\DataInterpreter\DataInterpreterInterface;
use Drupal\restful\Plugin\resource\Field\ResourceFieldCollection;

abstract class ResourceEntity extends Resource {

  /**
   * The entity type.
   *
   * @var string
   */
  protected $entityType;

  /**
   * The entity bundles.
   *
   * @var array
   */
  protected $bundles = array();

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    if (empty($plugin_definition['dataProvider']['entityType'])) {
      throw new InternalServerErrorException('The entity type was not provided.');
    }
    $this->entityType = $plugin_definition['dataProvider']['entityType'];
    if (isset($plugin_definition['dataProvider']['bundles'])) {
      $this->bundles = $plugin_definition['dataProvider']['bundles'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function dataProviderFactory() {
    $plugin_definition = $this->getPluginDefinition();
    $field_definitions = $this->getFieldDefinitions();
    if (!empty($plugin_definition['dataProvider']['viewMode'])) {
      $field_definitions_array = $this->viewModeFields($plugin_definition['dataProvider']['viewMode']);
      $field_definitions = ResourceFieldCollection::factory($field_definitions_array);
    }
    $class_name = '\Drupal\restful\Plugin\resource\DataProvider\DataProviderEntity';
    // TODO: Make this logic below alterable by the implementor user via info hook, or something similar.
    if ($this->getEntityType() == 'taxonomy_term') {
      $class_name = '\Drupal\restful\Plugin\resource\DataProvider\DataProviderTaxonomyTerm';
    }
    return new $class_name($this->getRequest(), $field_definitions, $this->getAccount(), $this->getPath(), $plugin_definition['dataProvider']);
  }

  /**
   * Gets the entity type.
   *
   * @return string
   *   The entity type.
   */
  public function getEntityType() {
    return $this->entityType;
  }

  /**
   * Gets the entity bundle.
   *
   * @return array
   *   The bundles.
   */
  public function getBundles() {
    return $this->bundles;
  }

  /**
   * Get the "self" url.
   *
   * @param DataInterpreterInterface $interpreter
   *   The wrapped entity.
   *
   * @return string
   *   The self URL.
   */
  public function getEntitySelf(DataInterpreterInterface $interpreter) {
    return $this->versionedUrl($interpreter->getWrapper()->getIdentifier());
  }

  /**
   * Get the public fields with the default values applied to them.
   *
   * @param array $field_definitions
   *   The field definitions to process.
   *
   * @return array
   *   The field definition array.
   */
  protected function processPublicFields(array $field_definitions) {
    // The fields that only contain a property need to be set to be
    // ResourceFieldEntity. Otherwise they will be considered regular
    // ResourceField.
    return array_map(function ($field_definition) {
      return $field_definition + array('class' => '\Drupal\restful\Plugin\resource\Field\ResourceFieldEntity');
    }, $field_definitions);
  }

  /**
   * Get the public fields with default values based on view mode information.
   *
   * @param array $view_mode_info
   *   View mode configuration array.
   *
   * @return array
   *   The public fields.
   *
   * @throws ServerConfigurationException
   */
  protected function viewModeFields(array $view_mode_info) {
    $field_definitions = array();
    $entity_type = $this->getEntityType();
    $bundles = $this->getBundles();
    $view_mode = $view_mode_info['name'];
    if (count($bundles) != 1) {
      throw new ServerConfigurationException('View modes can only be used in resources with a single bundle.');
    }
    $bundle = reset($bundles);
    foreach ($view_mode_info['fieldMap'] as $field_name => $public_field_name) {
      $field_instance = field_info_instance($entity_type, $field_name, $bundle);
      $formatter_info = $field_instance['display'][$view_mode];
      unset($formatter_info['module']);
      unset($formatter_info['weight']);
      unset($formatter_info['label']);
      $field_definitions[$public_field_name] = array(
        'property' => $field_name,
        'formatter' => $formatter_info,
      );
    }
    return $field_definitions;
  }

}

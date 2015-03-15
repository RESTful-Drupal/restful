<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\ResourceEntity.
 */

namespace Drupal\restful\Plugin\resource;

use Drupal\restful\Exception\InternalServerErrorException;
use Drupal\restful\Plugin\resource\DataProvider\DataProviderEntity;
use Drupal\restful\Plugin\resource\DataInterpreter\DataInterpreterInterface;

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
    return new DataProviderEntity($this->getRequest(), $this->getFieldDefinitions(), $this->getAccount(), $plugin_definition['dataProvider']);
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
   * @return array
   *   The field definition array.
   */
  protected function processedPublicFields() {
    // The fields that only contain a property need to be set to be
    // ResourceFieldEntity. Otherwise they will be considered regular
    // ResourceField.
    return array_map(function ($field_definition) {
      return $field_definition + array('class' => '\Drupal\restful\Plugin\resource\Field\ResourceFieldEntity');
    },$this->publicFields());
  }

}

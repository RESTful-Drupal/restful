<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\ResourceEntity.
 */

namespace Drupal\restful\Plugin\resource;

use Drupal\restful\Exception\InternalServerErrorException;
use Drupal\restful\Plugin\resource\DataProvider\DataProviderEntity;
use Drupal\restful\Plugin\resource\Field\ResourceFieldCollectionInterface;

class ResourceEntity extends Resource {

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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ResourceFieldCollectionInterface $field_definitions) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $field_definitions);
    if (empty($plugin_definition['entityType'])) {
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
   * @return string
   */
  public function getBundles() {
    return $this->bundles;
  }

}

<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\ResourceEntity.
 */

namespace Drupal\restful\Plugin\resource;

use Drupal\restful\Exception\InternalServerErrorException;
use Drupal\restful\Exception\ServerConfigurationException;
use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\DataInterpreter\DataInterpreterInterface;
use Drupal\restful\Plugin\resource\DataProvider\DataProviderEntityInterface;
use Drupal\restful\Plugin\resource\Field\ResourceFieldCollection;
use Drupal\restful\Plugin\resource\Field\ResourceFieldEntity;

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
    if (empty($plugin_definition['dataProvider']['entityType'])) {
      throw new InternalServerErrorException('The entity type was not provided.');
    }
    $this->entityType = $plugin_definition['dataProvider']['entityType'];
    if (isset($plugin_definition['dataProvider']['bundles'])) {
      $this->bundles = $plugin_definition['dataProvider']['bundles'];
    }
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Data provider factory.
   *
   * @return DataProviderEntityInterface
   *   The data provider for this resource.
   *
   * @throws ServerConfigurationException
   */
  public function dataProviderFactory() {
    $plugin_definition = $this->getPluginDefinition();
    $field_definitions = $this->getFieldDefinitions();
    if (!empty($plugin_definition['dataProvider']['viewMode'])) {
      $field_definitions_array = $this->viewModeFields($plugin_definition['dataProvider']['viewMode']);
      $field_definitions = ResourceFieldCollection::factory($field_definitions_array, $this->getRequest());
    }
    $class_name = $this->dataProviderClassName();
    if (!class_exists($class_name)) {
      throw new ServerConfigurationException(sprintf('The DataProvider could not be found for this resource: %s.', $this->getResourceMachineName()));
    }
    return new $class_name($this->getRequest(), $field_definitions, $this->getAccount(), $this->getPluginId(), $this->getPath(), $plugin_definition['dataProvider']);
  }

  /**
   * Data provider class.
   *
   * @return string
   *   The name of the class of the provider factory.
   */
  protected function dataProviderClassName() {
    // This helper function allows to map a resource to a different data
    // provider class.
    if ($this->getEntityType() == 'node') {
      return '\Drupal\restful\Plugin\resource\DataProvider\DataProviderNode';
    }
    elseif ($this->getEntityType() == 'taxonomy_term') {
      return '\Drupal\restful\Plugin\resource\DataProvider\DataProviderTaxonomyTerm';
    }
    elseif ($this->getEntityType() == 'file') {
      return '\Drupal\restful\Plugin\resource\DataProvider\DataProviderFile';
    }
    return '\Drupal\restful\Plugin\resource\DataProvider\DataProviderEntity';
  }

  /**
   * {@inheritdoc}
   */
  protected function publicFields() {
    $public_fields = array();
    $public_fields['id'] = array(
      'wrapper_method' => 'getIdentifier',
      'wrapper_method_on_entity' => TRUE,
      'methods' => array(RequestInterface::METHOD_GET, RequestInterface::METHOD_OPTIONS),
      'discovery' => array(
        // Information about the field for human consumption.
        'info' => array(
          'label' => t('ID'),
          'description' => t('Base ID for the entity.'),
        ),
        // Describe the data.
        'data' => array(
          'cardinality' => 1,
          'read_only' => TRUE,
          'type' => 'integer',
          'required' => TRUE,
        ),
      ),
    );
    $public_fields['label'] = array(
      'wrapper_method' => 'label',
      'wrapper_method_on_entity' => TRUE,
      'discovery' => array(
        // Information about the field for human consumption.
        'info' => array(
          'label' => t('Label'),
          'description' => t('The label of the resource.'),
        ),
        // Describe the data.
        'data' => array(
          'type' => 'string',
        ),
        // Information about the form element.
        'form_element' => array(
          'type' => 'textfield',
          'size' => 255,
        ),
      ),
    );
    $public_fields['self'] = array(
      'callback' => array($this, 'getEntitySelf'),
    );

    return $public_fields;
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
   * @throws \Drupal\restful\Exception\ServerConfigurationException
   *   For resources without ID field.
   *
   * @return array
   *   The field definition array.
   */
  protected function processPublicFields(array $field_definitions) {
    // The fields that only contain a property need to be set to be
    // ResourceFieldEntity. Otherwise they will be considered regular
    // ResourceField.
    return array_map(function ($field_definition) {
      $field_entity_class = '\Drupal\restful\Plugin\resource\Field\ResourceFieldEntity';
      $class_name = ResourceFieldEntity::fieldClassName($field_definition);
      if (!$class_name || is_subclass_of($class_name, $field_entity_class)) {
        $class_name = $field_entity_class;
      }
      return $field_definition + array('class' => $class_name, 'entityType' => $this->getEntityType());
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
        'entityType' => $this->getEntityType(),
      );
    }
    return $field_definitions;
  }

}

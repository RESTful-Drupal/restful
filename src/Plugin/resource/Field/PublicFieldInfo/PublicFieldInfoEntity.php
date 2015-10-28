<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Field\PublicFieldInfo\PublicFieldInfoEntity.
 */

namespace Drupal\restful\Plugin\resource\Field\PublicFieldInfo;

class PublicFieldInfoEntity extends PublicFieldInfoBase implements PublicFieldInfoEntityInterface {

  /**
   * The field name or property in the Drupal realm.
   *
   * @var string
   */
  protected $property;

  /**
   * The entity type.
   *
   * @var string
   */
  protected $entityType;

  /**
   * The bundle.
   *
   * @var string
   */
  protected $bundle;

  /**
   * PublicFieldInfoBase constructor.
   *
   * @param string $field_name
   *   The name of the field.
   * @param string $property
   *   The name of the Drupal field.
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   * @param array[] $sections
   *   The array of categories information.
   */
  public function __construct($field_name, $property, $entity_type, $bundle, array $sections = array()) {
    parent::__construct($field_name, $sections);
    $this->property = $property;
    $this->entityType = $entity_type;
    $this->bundle = $bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormSchemaAllowedValues() {
    if (!module_exists('options')) {
      return NULL;
    }
    $field_name = $this->property;
    if (!$field_info = field_info_field($field_name)) {
      return NULL;
    }
    if (!$field_instance = field_info_instance($this->entityType, $field_name, $this->bundle)) {
      return NULL;
    }
    if (!$this::formSchemaHasAllowedValues($field_info, $field_instance)) {
      // Field doesn't have allowed values.
      return NULL;
    }
    // Use Field API's widget to get the allowed values.
    $type = str_replace('options_', '', $field_instance['widget']['type']);
    $multiple = $field_info['cardinality'] > 1 || $field_info['cardinality'] == FIELD_CARDINALITY_UNLIMITED;
    // Always pass TRUE for "required" and "has_value", as we don't want to get
    // the "none" option.
    $required = TRUE;
    $has_value = TRUE;
    $properties = _options_properties($type, $multiple, $required, $has_value);
    // Mock an entity.
    $values = array();
    $entity_info = $this->getEntityInfo();
    if (!empty($entity_info['entity keys']['bundle'])) {
      // Set the bundle of the entity.
      $values[$entity_info['entity keys']['bundle']] = $this->bundle;
    }
    $entity = entity_create($this->entityType, $values);
    return _options_get_options($field_info, $field_instance, $properties, $this->entityType, $entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormSchemaAllowedType() {
    if (!module_exists('options')) {
      return NULL;
    }
    $field_name = $this->property;
    if (!$field_info = field_info_field($field_name)) {
      return NULL;
    }
    if (!$field_instance = field_info_instance($this->entityType, $field_name, $this->bundle)) {
      return NULL;
    }
    return $field_instance['widget']['type'];
  }

  /**
   * Get the entity info for the current entity the endpoint handling.
   *
   * @param string $type
   *   Optional. The entity type.
   *
   * @return array
   *   The entity info.
   *
   * @see entity_get_info().
   */
  protected function getEntityInfo($type = NULL) {
    return entity_get_info($type ? $type : $this->entityType);
  }

  /**
   * Determines if a field has allowed values.
   *
   * If Field is reference, and widget is autocomplete, so for performance
   * reasons we do not try to grab all the referenced entities.
   *
   * @param array $field
   *   The field info array.
   * @param array $field_instance
   *   The instance info array.
   *
   * @return bool
   *   TRUE if a field should be populated with the allowed values.
   */
  protected static function formSchemaHasAllowedValues($field, $field_instance) {
    $field_types = array(
      'entityreference',
      'taxonomy_term_reference',
      'field_collection',
      'commerce_product_reference',
    );
    $widget_types = array(
      'taxonomy_autocomplete',
      'entityreference_autocomplete',
      'entityreference_autocomplete_tags',
      'commerce_product_reference_autocomplete',
    );
    return !in_array($field['type'], $field_types) || !in_array($field_instance['widget']['type'], $widget_types);
  }

}

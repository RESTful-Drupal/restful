<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\ResourceField.
 */

namespace Drupal\restful\Plugin\resource\Field;

use Drupal\restful\Exception\ServerConfigurationException;

class ResourceField extends ResourceFieldBase implements ResourceFieldInterface {

  /**
   * Constructor.
   *
   * @param array $field
   *   Contains the field values.
   *
   * @throws ServerConfigurationException
   */
  public function __construct(array $field) {
    if (empty($field['public_name'])) {
      throw new ServerConfigurationException('No public name provided in the field mappings.');
    }
    $this->publicName = $field['public_name'];
    $this->accessCallbacks = $field['access_callbacks'];
    $this->property = $field['property'];
    $this->subProperty = $field['sub_property'];
    $this->formatter = $field['formatter'];
    $this->wrapperMethod = $field['wrapper_method'];
    $this->column = $field['column'];
    $this->callback = $field['callback'];
    $this->processCallbacks = $field['processCallbacks'];
    $this->resource = $field['resource'];
    $this->createOrUpdatePassthrough = $field['create_or_update_passthrough'];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(array $field) {
    if ($class_name = static::fieldClassName($field)) {
      // Call the create factory in the derived class.
      return call_user_func_array(array($class_name, 'create'), array($field, new static($field)));
    }
    // If no other class was found, then use the current one.
    $resource_field = new static($field);
    $resource_field->addDefaults();
    return $resource_field;
  }

  /**
   * {@inheritdoc}
   */
  public function addDefaults() {
    // Almost all the defaults come are applied by the object's property
    // defaults.

    foreach ($this->getResource() as &$resource) {
      // Expand array to be verbose.
      if (!is_array($resource)) {
        $resource = array('name' => $resource);
      }

      // Set default value.
      $resource += array(
        'full_view' => TRUE,
      );

      // Set the default value for the version of the referenced resource.
      if (empty($resource['major_version']) || empty($resource['minor_version'])) {
        list($major_version, $minor_version) = restful()
          ->getResourceManager()
          ->getResourceLastVersion($resource['name']);
        $resource['major_version'] = $major_version;
        $resource['minor_version'] = $minor_version;
      }
    }
  }

  /**
   * Get the class name to use based on the field definition.
   *
   * @param array $field_definition
   *   The processed field definition with the user values.
   *
   * @return string
   *   The class name to use. If the class name is empty or does not implement
   *   ResourceFieldInterface then ResourceField will be used. NULL if nothing
   *   was found.
   */
  protected static function fieldClassName(array $field_definition) {
    if (!empty($field_definition['class'])) {
      $class_name = $field_definition['class'];
    }
    // Search for indicators that this is a ResourceFieldEntityInterface.
    elseif (
      !empty($field_definition['sub_property']) ||
      !empty($field_definition['formatter']) ||
      !empty($field_definition['wrapper_method']) ||
      !empty($field_definition['wrapper_method_on_entity']) ||
      !empty($field_definition['column']) ||
      !empty($field_definition['image_styles'])
    ) {
      $class_name = 'ResourceFieldEntity';
    }

    if (
      !empty($class_name) &&
      class_exists($class_name) &&
      in_array(
        'Drupal\restful\Plugin\resource\Field\ResourceFieldInterface',
        class_implements($field_definition['class'])
      )
    ) {
      return $field_definition['class'];
    }

    return NULL;
  }

}

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
   * @param array $fields
   *   Contains the field values.
   *
   * @throws ServerConfigurationException
   */
  public function __construct(array $fields) {
    if (empty($fields['public_name'])) {
      throw new ServerConfigurationException('No public name provided in the field mappings.');
    }
    $this->publicName = $fields['public_name'];
    $this->accessCallbacks = $fields['access_callbacks'];
    $this->property = $fields['property'];
    $this->subProperty = $fields['sub_property'];
    $this->formatter = $fields['formatter'];
    $this->wrapperMethod = $fields['wrapper_method'];
    $this->column = $fields['column'];
    $this->callback = $fields['callback'];
    $this->processCallbacks = $fields['processCallbacks'];
    $this->resource = $fields['resource'];
    $this->createOrUpdatePassthrough = $fields['create_or_update_passthrough'];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(array $fields) {
    $resource_field = new static($fields);
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

}

<?php

/**
 * @file
 * Contains \RestfulEntityViewMode
 *
 * Helper class to deal with rendering entity view modes.
 */

class RestfulEntityViewMode {

  /**
   * Entity type
   *
   * @var string
   */
  protected $entityType;

  /**
   * Bundle
   *
   * @var string
   */
  protected $bundle;

  /**
   * Array that caches the render elemens for different view modes.
   *
   * @var array
   */
  protected $renders;

  /**
   * Constructor.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   */
  public function __construct($entity_type, $bundle) {
    $this->entityType = $entity_type;
    $this->bundle = $bundle;
  }

  /**
   * Generates the public properties configuration array from the mappings.
   *
   * @param string $view_mode
   *   The view mode.
   * @param array $field_map
   *   Associative array that maps field names to public properties.
   *
   * @throws \RestfulServerConfigurationException
   *
   * @return array
   *   The public properties info array.
   */
  public function mapFields($view_mode, $field_map) {
    $displayed_fields = $this->displayedFieldsList($view_mode);

    // Set the mappings from the field name to the output key.
    $public_fields = array();
    foreach ($displayed_fields as $field_name) {
      if (empty($field_map[$field_name])) {
        throw new \RestfulServerConfigurationException(format_string('No mapping was found for @field_name.', array(
          '@field_name' => $field_name,
        )));
      }

      // Add it to the public fields array with a special callback function.
      $public_fields[$field_map[$field_name]] = array(
        'callback' => array(
          array($this, 'renderField'),
          array($field_name, $view_mode),
        ),
      );
    }
    if (empty($public_fields)) {
      throw new \RestfulServerConfigurationException('No fields shown rendering entity view mode.');
    }

    return $public_fields;
  }

  /**
   * Helper method to get all the displayed fields for a bundle and a view_mode.
   *
   * @param string $view_mode
   *   The view mode.
   *
   * @return array
   *   An array of field names that are displayed in this view mode.
   */
  protected function displayedFieldsList($view_mode) {
    $entity_field_instances = field_info_instances($this->entityType);
    $bundle_field_instances = reset($entity_field_instances);
    if ($bundle = $this->bundle) {
      $bundle_field_instances = $entity_field_instances[$bundle];
    }
    // Traverse the field instance to get all non hidden fields.
    $displayed_fields = array_map(function ($field_instance) use ($view_mode) {
      if (empty($field_instance['display'][$view_mode]) || $field_instance['display'][$view_mode]['type'] == 'hidden' || $field_instance['deleted']) {
        return NULL;
      }
      return $field_instance['field_name'];
    }, $bundle_field_instances);

    // Remove all NULL fields.
    return array_filter(array_values($displayed_fields));
  }

  /**
   * Helper function to get the rendered field for the output.
   *
   * @param \EntityDrupalWrapper $wrapper
   *   The entity wrapper.
   * @param string $field_name
   *   The field name to render.
   * @param string $view_mode
   *   The view mode to use.
   *
   * @return mixed
   *   The output value to be rendered.
   */
  public function renderField(\EntityDrupalWrapper $wrapper, $field_name, $view_mode) {
    $render_element = $this->getEntityRenderElement($wrapper, $view_mode);
    return drupal_render($render_element[$field_name]);
  }

  /**
   * Generate the render element from the entity and view mode.
   *
   * @param \EntityDrupalWrapper $wrapper
   *   The entity wrapper.
   * @param string $view_mode
   *   The view mode to use.
   *
   * @return array
   *   A render array for the current entity.
   */
  protected function getEntityRenderElement(\EntityDrupalWrapper $wrapper, $view_mode) {
    $entity_id = $wrapper->getIdentifier();
    if (empty($this->renders[$view_mode][$entity_id])) {
      $render_element = $wrapper->view($view_mode);
      $this->renders[$view_mode][$entity_id] = $render_element[$this->entityType][$entity_id];
    }
    return $this->renders[$view_mode][$entity_id];
  }

}

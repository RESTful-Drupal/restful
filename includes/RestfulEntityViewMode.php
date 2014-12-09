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
   * Helper method to get all the displayed fields for a bundle and a view_mode.
   *
   * @param string $view_mode
   *   The view mode.
   *
   * @return array
   *   An array of field names that are displayed in this view mode.
   */
  function displayedFieldsList($view_mode) {
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
    $render_field_element = $render_element[$this->entityType][$wrapper->getIdentifier()][$field_name];
    return drupal_render($render_field_element);
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
    if (empty($this->renders[$view_mode])) {
      $this->renders[$view_mode] = $wrapper->view($view_mode);
    }
    return $this->renders[$view_mode];
  }

}

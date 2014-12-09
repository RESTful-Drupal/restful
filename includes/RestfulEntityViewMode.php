<?php

/**
 * @file
 * Contains \RestfulEntityViewMode
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
  function getDisplayedFields($view_mode) {
    $entity_field_instances = field_info_instances($this->entityType);
    $bundle_field_instances = reset($entity_field_instances);
    if ($bundle = $this->bundle) {
      $bundle_field_instances = $entity_field_instances[$bundle];
    }
    $displayed_fields = array_map(function ($field_instance) use ($view_mode) {
      if (empty($field_instance['display'][$view_mode]) || $field_instance['display'][$view_mode]['type'] == 'hidden' || $field_instance['deleted']) {
        return NULL;
      }
      return $field_instance['field_name'];
    }, $bundle_field_instances);

    // Remove all NULL fields.
    return array_filter(array_values($displayed_fields));
  }
}

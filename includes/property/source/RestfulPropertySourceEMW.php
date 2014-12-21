<?php

/**
 * @file
 * Contains \RestfulPropertySourceEMW.
 */

class RestfulPropertySourceEMW extends \RestfulPropertySourceBase implements \RestfulPropertySourceInterface {

  /**
   * Constructor.
   *
   * @param \EntityDrupalWrapper $source
   *   Contains the data object.
   */
  public function __construct(\EntityDrupalWrapper $source) {
    $this->source = $source;
  }

  /**
   * {@inheritdoc}
   */
  public function get($key, $delta = NULL) {
    $context = $this->getContext();
    $method = $context['wrapper_method'];
    $resource = $context['resource'] ?: NULL;
    $formatter = $context['formatter'] ?: NULL;
    $item_wrapper = $this->itemWrapper($key, $delta);

    if ($resource) {
      $value = $this->getValueFromResource($item_wrapper, $key, $resource);
    }
    elseif ($formatter) {
      $value = $this->getValueFromFieldFormatter($this->itemWrapper($key));
    }
    else {
      // Wrapper method.
      $value = $item_wrapper->{$method}();
    }

    return $value;
  }

  /**
   * Get value from an entity reference field with "resource" property.
   *
   * @param EntityMetadataWrapper $wrapper
   *   The wrapped object.
   * @param string $property
   *   The property name (i.e. the field name).
   * @param array $resource
   *   Array with resource names, keyed by the bundle name.
   *
   * @return mixed
   *   The value if found, or NULL if bundle not defined.
   */
  protected function getValueFromResource(EntityMetadataWrapper $wrapper, $property, $resource) {
    if (!$entity = $wrapper->value()) {
      return;
    }

    $target_type = static::getTargetTypeFromEntityReference($wrapper, $property);
    list($id,, $bundle) = entity_extract_ids($target_type, $entity);

    if (empty($resource[$bundle])) {
      // Bundle not mapped to a resource.
      return;
    }

    if (!empty($resource[$bundle]['metadata_view'])) {
      return array(
        'id' => $id,
        'entity_type' => $target_type,
        'bundle' => $bundle,
        'resource_name' => $resource[$bundle]['name'],
      );
    }
    elseif (empty($resource[$bundle]['full_view'])) {
      // Show only the ID(s) of the referenced resource.
      return $wrapper->value(array('identifier' => TRUE));
    }

    $handler = restful_get_restful_handler($resource[$bundle]['name'], $resource[$bundle]['major_version'], $resource[$bundle]['minor_version']);
    return $handler->viewEntity($id);
  }

  /**
   * Get the "target_type" property from an field or property reference.
   *
   * @param \EntityMetadataWrapper $wrapper
   *   The wrapped property.
   * @param $property
   *   The public field name.
   *
   * @return string
   *   The target type of the referenced entity.
   *
   * @throws \RestfulException
   */
  protected static function getTargetTypeFromEntityReference(\EntityMetadataWrapper $wrapper, $property) {
    $params = array('@property' => $property);

    if ($field = field_info_field($property)) {
      if ($field['type'] == 'entityreference') {
        return $field['settings']['target_type'];
      }
      elseif ($field['type'] == 'taxonomy_term_reference') {
        return 'taxonomy_term';
      }

      throw new \RestfulException(format_string('Field @property is not an entity reference or taxonomy reference field.', $params));
    }
    else {
      // This is a property referencing another entity (e.g. the "uid" on the
      // node object).
      $info = $wrapper->info();
      if (entity_get_info($info['type'])) {
        return $info['type'];
      }

      throw new \RestfulException(format_string('Property @property is not defined as reference in the EntityMetadataWrapper definition.', $params));
    }
  }

  /**
   * Get value from a field rendered by Drupal field API's formatter.
   *
   * @param EntityMetadataWrapper $item_wrapper
   *   The wrapped property.
   *
   * @throws RestfulServerConfigurationException
   *
   * @return mixed
   *   A single or multiple values.
   */
  protected function getValueFromFieldFormatter(\EntityMetadataWrapper $item_wrapper) {
    $wrapper = $this->getSource();
    $context = $this->getContext();
    $property = $context['property'];
    $value = NULL;

    if (!field_info_field($property)) {
      // Property is not a field.
      throw new \RestfulServerConfigurationException(format_string('@property is not a configurable field, so it cannot be processed using field API formatter', array('@property' => $property)));
    }

    // Get values from the formatter.
    $entity_info = $this->getSource()->info();
    $output = field_view_field($entity_info['type'], $wrapper->value(), $property, $context['formatter']);

    // Unset the theme, as we just want to get the value from the formatter,
    // without the wrapping HTML.
    unset($output['#theme']);

    if ($this->isMultiple()) {
      // Multiple values.
      foreach (element_children($output) as $delta) {
        $value[] = drupal_render($output[$delta]);
      }
    }
    else {
      // Single value.
      $value = drupal_render($output);
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function isMultiple() {
    return $this->itemWrapper() instanceof EntityListWrapper;
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    if ($this->isMultiple()) {
      return $this->itemWrapper()->count();
    }
    return 1;
  }

  /**
   * Get the item wrapper.
   *
   * @param string $property
   *   The name of the property to get.
   * @param int $delta
   *   The delta.
   *
   * @return \EntityDrupalWrapper
   *   The sub wrapper.
   */
  protected function itemWrapper($property = NULL, $delta = NULL) {
    $context = $this->getContext();
    if (!isset($property)) {
      $property = $context['property'];
    }
    $wrapper = $this->getSource();
    $item_wrapper = $context['wrapper_method_on_entity'] ? $wrapper : $wrapper->{$property};
    if (isset($delta)) {
      $item_wrapper = $item_wrapper->get($delta);
    }
    if ($context['sub_property'] && $item_wrapper->{$context['wrapper_method']}()) {
      $item_wrapper = $item_wrapper->{$context['sub_property']};
    }
    return $item_wrapper;
  }

}

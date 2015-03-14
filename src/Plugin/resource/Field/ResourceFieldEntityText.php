<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Field\ResourceFieldEntityText.
 */

namespace Drupal\restful\Plugin\resource\Field;

class ResourceFieldEntityText extends ResourceFieldEntity implements ResourceFieldEntityInterface {

  /**
   * {@inheritdoc}
   */
  public function preprocess($value) {
    // Text field. Check if field has an input format.

    // This is a big leap of faith, but here we go... In order to have more
    // flexible resources we allow multiple bundles per resource. That means
    // that if we are using a field that is not present in all of the bundles we
    // will return NULL for those bundles that it does not exist. The problem
    // comes when two different bundles declare the same field but with
    // different instance values. In that situation we loop through the bundles
    // to find one where the field instance is configured and assume that all
    // bundles have that same configuration. Keep calm and keep reading.
    $instance = FALSE;
    $field_info = field_info_field($this->getProperty());
    foreach ($this->getBundles() as $bundle) {
      if ($instance = field_info_instance($this->getEntityType(), $this->getProperty(), $bundle)) {
        break;
      }
    }
    // If there was no bundle that had the field instance, then return NULL.
    if (!$instance) {
      return NULL;
    }

    $return = NULL;
    if ($field_info['cardinality'] == 1) {
      // Single value.
      if (!$instance['settings']['text_processing']) {
        return $value;
      }

      return array(
        'value' => $value,
        // TODO: This is hardcoded! Fix it.
        'format' => 'filtered_html',
      );
    }

    // Multiple values.
    foreach ($value as $delta => $single_value) {
      if (!$instance['settings']['text_processing']) {
        $return[$delta] = $single_value;
      }
      else {
        $return[$delta] = array(
          'value' => $single_value,
          'format' => 'filtered_html',
        );
      }
    }
    return $return;
  }

}

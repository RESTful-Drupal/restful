<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Field\ResourceFieldEntityText.
 */

namespace Drupal\restful\Plugin\resource\Field;

use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\DataInterpreter\DataInterpreterInterface;

class ResourceFieldEntityText extends ResourceFieldEntity implements ResourceFieldEntityInterface {

  /**
   * {@inheritdoc}
   */
  public function preprocess($value) {
    // Text field. Check if field has an input format.

    $field_info = field_info_field($this->getProperty());
    // If there was no bundle that had the field instance, then return NULL.
    if (!$instance = field_info_instance($this->getEntityType(), $this->getProperty(), $this->getBundle())) {
      return NULL;
    }

    $return = NULL;
    if ($field_info['cardinality'] == 1) {
      // Single value.
      if (!$instance['settings']['text_processing']) {
        return $value;
      }
      if (isset($value['value'], $value['format'])) {
        return array(
          'value' => $value['value'],
          'format' => $value['format'],
        );
      }
      // Fallback to the initial behavior to support BC.
      return array(
        'value' => $value,
        'format' => 'filtered_html',
      );
    }

    // Multiple values.
    foreach ($value as $delta => $single_value) {
      if (!$instance['settings']['text_processing']) {
        $return[$delta] = $single_value;
      }
      elseif (isset($single_value['value'], $single_value['format'])) {
        $return[$delta] = array(
          'value' => $single_value['value'],
          'format' => $single_value['format'],
        );
      }
      else {
        // Fallback to the initial behavior to support BC.
        $return[$delta] = array(
          'value' => $single_value,
          'format' => 'filtered_html',
        );
      }
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function executeProcessCallbacks($value) {
    return $this->decorated->executeProcessCallbacks($value);
  }

  /**
   * {@inheritdoc}
   */
  public function getRequest() {
    return $this->decorated->getRequest();
  }

  /**
   * {@inheritdoc}
   */
  public function setRequest(RequestInterface $request) {
    $this->decorated->setRequest($request);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinition() {
    return $this->decorated->getDefinition();
  }

}

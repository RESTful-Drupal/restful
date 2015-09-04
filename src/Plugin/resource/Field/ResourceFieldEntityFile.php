<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Field\ResourceFieldEntityFile
 */

namespace Drupal\restful\Plugin\resource\Field;

use Drupal\restful\Http\RequestInterface;

class ResourceFieldEntityFile extends ResourceFieldEntity implements ResourceFieldEntityInterface {

  /**
   * {@inheritdoc}
   */
  public function preprocess($value) {
    $field_info = field_info_field($this->getProperty());
    if ($field_info['cardinality'] == 1) {
      // Single value.
      return array(
        'fid' => $value,
        'display' => TRUE,
      );
    }

    $value = is_array($value) ? $value : explode(',', $value);
    $return = array();
    foreach ($value as $delta => $single_value) {
      $return[$delta] = array(
        'fid' => $single_value,
        'display' => TRUE,
      );
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

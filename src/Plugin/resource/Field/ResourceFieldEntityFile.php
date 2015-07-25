<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Field\ResourceFieldEntityFile
 */

namespace Drupal\restful\Plugin\resource\Field;

class ResourceFieldEntityFile extends ResourceFieldEntity implements ResourceFieldEntityInterface {

  /**
   * Interpreter to use to interact with the field.
   *
   * @var DataInterpreterInterface
   */
  protected $interpreter;

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
  public function setInterpreter($interpreter) {
    // Don't use a decorator for this, it leads to the same interpreter being
    // assigned to the same memory object all of the results in a list call.
    $this->interpreter = $interpreter;
  }

  /**
   * {@inheritdoc}
   */
  public function getInterpreter() {
    return $this->interpreter;
  }

  /**
   * {@inheritdoc}
   */
  public function executeProcessCallbacks($value) {
    return $this->decorated->executeProcessCallbacks($value);
  }

}

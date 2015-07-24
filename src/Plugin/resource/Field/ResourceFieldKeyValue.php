<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Field\ResourceFieldKeyValue.
 */

namespace Drupal\restful\Plugin\resource\Field;


use Drupal\restful\Plugin\resource\DataInterpreter\DataInterpreterInterface;

class ResourceFieldKeyValue extends ResourceField implements ResourceFieldInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(array $field) {
    $resource_field = new static($field);
    $resource_field->addDefaults();
    return $resource_field;
  }

  /**
   * {@inheritdoc}
   */
  public function value(DataInterpreterInterface $interpreter = NULL) {
    $interpreter = $interpreter ?: $this->getInterpreter();
    if ($value = parent::value($interpreter)) {
      return $value;
    }
    return $interpreter->getWrapper()->get($this->getProperty());
  }

}

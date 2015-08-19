<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Field\ResourceFieldKeyValue.
 */

namespace Drupal\restful\Plugin\resource\Field;

use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\DataInterpreter\DataInterpreterInterface;

class ResourceFieldKeyValue extends ResourceField implements ResourceFieldInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(array $field, RequestInterface $request = NULL) {
    $request = $request ?: restful()->getRequest();
    $resource_field = new static($field, $request);
    $resource_field->addDefaults();
    return $resource_field;
  }

  /**
   * {@inheritdoc}
   */
  public function value(DataInterpreterInterface $interpreter) {
    if ($value = parent::value($interpreter)) {
      return $value;
    }
    return $interpreter->getWrapper()->get($this->getProperty());
  }

}

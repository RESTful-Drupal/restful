<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Field\ResourceFieldDbColumn.
 */

namespace Drupal\restful\Plugin\resource\Field;

use Drupal\restful\Exception\ServerConfigurationException;

class ResourceFieldDbColumn extends ResourceField implements ResourceFieldDbColumnInterface {

  /**
   * Column for query.
   * @var string
   */
  protected $columnForQuery;

  /**
   * Constructor.
   *
   * @param array $field
   *   Contains the field values.
   *
   * @throws ServerConfigurationException
   */
  public function __construct(array $field) {
    parent::__construct($field);
    $this->columnForQuery = empty($field['columnForQuery']) ? $this->getProperty() : $field['columnForQuery'];
  }

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
  public function getColumnForQuery() {
    return $this->columnForQuery;
  }

}

<?php

/**
 * @file
 * Contains \Drupal\restful\Util\RelationalFilter.
 */

namespace Drupal\restful\Util;

class RelationalFilter implements RelationalFilterInterface {

  /**
   * Name of the field or property.
   *
   * @var string
   */
  protected $name;

  /**
   * Type of filter: field or property.
   *
   * @var string
   */
  protected $type;

  /**
   * Is destination filter.
   *
   * @var bool
   */
  protected $isDestination;

  /**
   * The referenced entity type.
   *
   * @var string
   */
  protected $entityType;

  /**
   * The referenced bundles.
   *
   * @var string[]
   */
  protected $bundles = array();

  /**
   * Database column for field filters.
   *
   * @var string
   */
  protected $column;

  /**
   * Constructs the RelationalFilter object.
   *
   * @param string $name
   * @param string $type
   * @param string $column
   * @param bool $is_destiantion
   * @param string $entity_type
   * @param array $bundles
   */
  public function __construct($name, $type, $column, $is_destiantion, $entity_type, array $bundles = array()) {
    $this->name = $name;
    $this->type = $type;
    $this->column = $column;
    $this->isDestination = $is_destiantion;
    $this->entityType = $entity_type;
    $this->bundles = $bundles;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function isIsDestination() {
    return $this->isDestination;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType() {
    return $this->entityType;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundles() {
    return $this->bundles;
  }

  /**
   * {@inheritdoc}
   */
  public function getColumn() {
    return $this->column;
  }

}

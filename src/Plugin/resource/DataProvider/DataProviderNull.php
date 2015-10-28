<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\DataProvider\DataProviderNull.
 */

namespace Drupal\restful\Plugin\resource\DataProvider;

class DataProviderNull extends DataProvider implements DataProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function count() {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function create($object) {
    return array();
  }

  /**
   * {@inheritdoc}
   */

  public function view($identifier) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $identifiers) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function update($identifier, $object, $replace = FALSE) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function remove($identifier) {}

  /**
   * {@inheritdoc}
   */
  public function getIndexIds() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  protected function initDataInterpreter($identifier) {
    return NULL;
  }

}

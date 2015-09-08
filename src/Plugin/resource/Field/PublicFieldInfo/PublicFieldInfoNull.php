<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Field\PublicFieldInfo\PublicFieldInfoNull.
 */

namespace Drupal\restful\Plugin\resource\Field\PublicFieldInfo;

class PublicFieldInfoNull implements PublicFieldInfoInterface {

  /**
   * PublicFieldInfoBase constructor.
   *
   * @param string $field_name
   *   The name of the field.
   * @param array[] $sections
   *   The array of categories information.
   */
  public function __construct($field_name, array $sections = array()) {
    $this->fieldName = $field_name;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function addCategory($category_name, array $section_info) {}

  /**
   * {@inheritdoc}
   */
  public function getSection($section_name) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function addSectionDefaults($section_name, array $section_info) {}

}

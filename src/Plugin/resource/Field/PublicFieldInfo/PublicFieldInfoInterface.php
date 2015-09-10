<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Field\PublicFieldInfo\PublicFieldInfoInterface.
 */

namespace Drupal\restful\Plugin\resource\Field\PublicFieldInfo;

interface PublicFieldInfoInterface {

  /**
   * Generates an structured array ready to be encoded.
   *
   * @return array
   *   The structured array of information.
   */
  public function prepare();

  /**
   * Add categories to the field info.
   *
   * @param string $category_name
   *   The name of the categories. By default RESTful suports 'info',
   *   'form_element' and 'data'.
   * @param array $section_info
   *   The structured array with the section information.
   */
  public function addCategory($category_name, array $section_info);

  /**
   * Gets the section.
   *
   * @param string $section_name
   *   The name of the section. By default RESTful suports 'info',
   *   'form_element' and 'data'.
   *
   * @return array
   *   The structured array with the section information.
   */
  public function getSection($section_name);

  /**
   * Merges default data in a section if it's not populated.
   *
   * @param string $section_name
   *   The name of the categories. By default RESTful suports 'info',
   *   'form_element' and 'data'.
   * @param array $section_info
   *   The structured array with the section information.
   */
  public function addSectionDefaults($section_name, array $section_info);

}

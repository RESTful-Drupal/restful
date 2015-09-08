<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Field\PublicFieldInfo\PublicFieldInfoBase.
 */

namespace Drupal\restful\Plugin\resource\Field\PublicFieldInfo;

use Drupal\restful\Exception\ServerConfigurationException;

class PublicFieldInfoBase implements PublicFieldInfoInterface {

  /**
   * Default sections for the field information.
   *
   * @var array[]
   */
  protected static $defaultSections = array(
    'info' => array(
      'label' => '',
      'description' => '',
    ),
    // Describe the data.
    'data' => array(
      'type' => NULL,
      'read_only' => FALSE,
      'cardinality' => 1,
      'required' => FALSE,
    ),
    // Information about the form element.
    'form_element' => array(
      'type' => NULL,
      'default_value' => '',
      'placeholder' => '',
      'size' => NULL,
      'allowed_values' => NULL,
    ),
  );

  /**
   * Sections for the field information.
   *
   * @var array[]
   */
  protected $sections = array();

  /**
   * The name of the public field.
   *
   * @var string
   */
  protected $fieldName = '';

  /**
   * PublicFieldInfoBase constructor.
   *
   * @param string $field_name
   *   The name of the field.
   * @param array[] $sections
   *   The array of sections information.
   */
  public function __construct($field_name, array $sections = array()) {
    $this->fieldName = $field_name;
    $sections = drupal_array_merge_deep($this::$defaultSections, $sections);
    foreach ($sections as $section_name => $section_info) {
      $this->addSection($section_name, $section_info);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function prepare() {
    return $this->sections;
  }

  /**
   * {@inheritdoc}
   */
  public function addSection($section_name, array $section_info) {
    try {
      $this->validate($section_name, $section_info);
      // Process the section info adding defaults if needed.
      $this->sections[$section_name] = $this->process($section_name, $section_info);
    }
    catch (ServerConfigurationException $e) {
      // If there are validation errors do not add the section.
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSection($section_name) {
    return empty($this->sections[$section_name]) ? array() : $this->sections[$section_name];
  }

  /**
   * {@inheritdoc}
   */
  public function addSectionDefaults($section_name, array $section_info) {
    $this->addSection($section_name, array_merge(
      $section_info,
      $this->getSection($section_name)
    ));
  }

  /**
   * Validates the provided data for the section.
   *
   * @param string $section_name
   *   The name of the sections. By default RESTful suports 'info',
   *   'form_element' and 'data'.
   * @param array $section_info
   *   The structured array with the section information.
   *
   * @throws ServerConfigurationException
   *   If the field does not pass validation.
   */
  protected function validate($section_name, array $section_info) {
    if ($section_name == 'info') {
      $this->validateInfo($section_info);
    }
    elseif ($section_name == 'data') {
      $this->validateData($section_info);
    }
    elseif ($section_name == 'form_element') {
      $this->validateFormElement($section_info);
    }
  }

  /**
   * Processes the provided data for the section.
   *
   * @param string $section_name
   *   The name of the sections. By default RESTful suports 'info',
   *   'form_element' and 'data'.
   * @param array $section_info
   *   The structured array with the section information.
   *
   * @returns array
   *   The processed section info.
   */
  protected function process($section_name, array $section_info) {
    if ($section_name == 'data') {
      if ($section_info['type'] == 'string') {
        $section_info['size'] = isset($section_info['size']) ? $section_info['size'] : 255;
      }
    }
    elseif ($section_name == 'form_element') {
      // Default title and description to the ones in the 'info' section.
      if (empty($section_info['title'])) {
        $section_info['title'] = empty($this->sections['info']['title']) ? $this->fieldName : $this->sections['info']['title'];
        if (!empty($this->sections['info']['description'])) {
          $section_info['description'] = $this->sections['info']['description'];
        }
      }
    }

    return $section_info;
  }

  /**
   * Validates the info section.
   *
   * @param array $section_info
   *   The structured array with the section information.
   *
   * @throws ServerConfigurationException
   *   If the field does not pass validation.
   */
  protected function validateInfo(array $section_info) {
    if (empty($section_info['label'])) {
      throw new ServerConfigurationException(sprintf('The basic information is not valid for this field: %s.', $this->fieldName));
    }
  }

  /**
   * Validates the info section.
   *
   * @param array $section_info
   *   The structured array with the section information.
   *
   * @throws ServerConfigurationException
   *   If the field does not pass validation.
   */
  protected function validateData(array $section_info) {
    if (empty($section_info['type'])) {
      throw new ServerConfigurationException(sprintf('The schema information is not valid for this field: %s.', $this->fieldName));
    }
  }

  /**
   * Validates the info section.
   *
   * @param array $section_info
   *   The structured array with the section information.
   *
   * @throws ServerConfigurationException
   *   If the field does not pass validation.
   */
  protected function validateFormElement(array $section_info) {
    if (empty($section_info['type'])) {
      throw new ServerConfigurationException(sprintf('The form element information is not valid for this field: %s.', $this->fieldName));
    }
  }

}

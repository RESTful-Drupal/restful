<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\ResourceDbQuery.
 */

namespace Drupal\restful\Plugin\resource;

abstract class ResourceDbQuery extends Resource implements ResourceInterface {

  /**
   * Get the public fields with the default values applied to them.
   *
   * @param array $field_definitions
   *   The field definitions to process.
   *
   * @return array
   *   The field definition array.
   */
  protected function processPublicFields(array $field_definitions) {
    // The fields that only contain a property need to be set to be
    // ResourceFieldEntity. Otherwise they will be considered regular
    // ResourceField.
    return array_map(function ($field_definition) {
      return $field_definition + array('class' => '\Drupal\restful\Plugin\resource\Field\ResourceFieldDbColumn');
    }, $field_definitions);
  }

  /**
   * Data provider class.
   *
   * @return string
   *   The name of the class of the provider factory.
   */
  protected function dataProviderClassName() {
    return '\Drupal\restful\Plugin\resource\DataProvider\DataProviderDbQuery';
  }

}

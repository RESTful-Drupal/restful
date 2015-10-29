<?php

/**
 * @file
 * Contains \Drupal\restful_example\Plugin\resource\variables\Variables__1_0.
 */

namespace Drupal\restful_example\Plugin\resource\variables;

use Drupal\restful\Plugin\resource\Resource;
use Drupal\restful\Plugin\resource\ResourceInterface;

/**
 * Class Variables
 * @package Drupal\restful\Plugin\resource
 *
 * @Resource(
 *   name = "variables:1.0",
 *   resource = "variables",
 *   label = "Variables",
 *   description = "Export the variables.",
 *   authenticationTypes = TRUE,
 *   authenticationOptional = TRUE,
 *   dataProvider = {
 *     "idField": "variable_name"
 *   },
 *   renderCache = {
 *     "render": true
 *   },
 *   majorVersion = 1,
 *   minorVersion = 0
 * )
 */
class Variables__1_0 extends Resource implements ResourceInterface {

  /**
   * {@inheritdoc}
   */
  protected function publicFields() {
    return array(
      'variable_name' => array('property' => 'name'),
      'variable_value' => array('property' => 'value'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function dataProviderClassName() {
    return '\Drupal\restful_example\Plugin\resource\variables\DataProviderVariable';
  }

}

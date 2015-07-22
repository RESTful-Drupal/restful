<?php

/**
 * @file
 * Contains \Drupal\restful_test\Plugin\resource\taxonomy_term\v1\TestTags__1_0;
 */

namespace Drupal\restful_test\Plugin\resource\taxonomy_term\v1;
use Drupal\restful\Plugin\resource\ResourceEntity;

/**
 * Class TestTags
 * @package Drupal\restful\Plugin\resource
 *
 * @Resource(
 *   name = "test_tags:1.0",
 *   resource = "test_tags",
 *   label = "Test Tags",
 *   description = "Export the tag taxonomy terms.",
 *   authenticationTypes = TRUE,
 *   authenticationOptional = TRUE,
 *   dataProvider = {
 *     "entityType": "taxonomy_term",
 *     "bundles": {
 *       "tags"
 *     },
 *   },
 *   majorVersion = 1,
 *   minorVersion = 0
 * )
 */
class TestTags__1_0 extends ResourceEntity {

  /**
   * {@inheritdoc}
   */
  protected function dataProviderClassName() {
    return '\Drupal\restful_test\Plugin\resource\taxonomy_term\v1\DataProviderTaxonomyTerm';
  }

}

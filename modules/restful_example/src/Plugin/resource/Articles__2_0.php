<?php

/**
 * @file
 * Contains \Drupal\restful_example\Plugin\resource\Articles__1_0.
 */

namespace Drupal\restful_example\Plugin\resource;

use Drupal\restful\Plugin\resource\ResourceEntity;
use Drupal\restful\Plugin\resource\ResourceInterface;

/**
 * Class Articles
 * @package Drupal\restful\Plugin\resource
 *
 * @Resource(
 *   name = "articles:2.0",
 *   resource = "articles",
 *   label = "Articles",
 *   description = "Export the article content type with authentication.",
 *   authenticationTypes = TRUE,
 *   authenticationOptional = TRUE,
 *   dataProvider = {
 *     "entityType": "node",
 *     "bundles": {
 *       "article"
 *     },
 *     "viewMode": {
 *       "name": "default",
 *       "fieldMap": {
 *         "body": "body",
 *         "field_tags": "tags",
 *         "field_image": "image",
 *       }
 *     }
 *   },
 *   majorVersion = 2,
 *   minorVersion = 0
 * )
 */
class Articles__2_0 extends ResourceEntity implements ResourceInterface {

  /**
   * {@inheritdoc}
   */
  protected function publicFields() {
    return array();
  }

}

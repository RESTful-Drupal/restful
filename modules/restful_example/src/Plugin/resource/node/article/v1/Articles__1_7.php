<?php

/**
 * @file
 * Contains \Drupal\restful_example\Plugin\resource\node\article\v1\Articles__1_7.
 */

namespace Drupal\restful_example\Plugin\resource\node\article\v1;

use Drupal\restful\Plugin\resource\ResourceInterface;
use Drupal\restful\Plugin\resource\ResourceNode;

/**
 * Class Articles__1_7
 * @package Drupal\restful\Plugin\resource
 *
 * @Resource(
 *   name = "articles:1.7",
 *   resource = "articles",
 *   label = "Articles",
 *   description = "Export the article content type using view modes.",
 *   authenticationTypes = TRUE,
 *   authenticationOptional = TRUE,
 *   dataProvider = {
 *     "entityType": "node",
 *     "bundles": {
 *       "article"
 *     },
 *     "viewMode" = {
 *       "name": "default",
 *       "fieldMap": {
 *         "body": "body",
 *         "field_tags": "tags",
 *         "field_image": "image",
 *       }
 *     }
 *   },
 *   majorVersion = 1,
 *   minorVersion = 7
 * )
 */
class Articles__1_7 extends ResourceNode implements ResourceInterface {}

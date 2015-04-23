<?php

/**
 * @file
 * Contains \Drupal\restful_example\Plugin\resource\node\article\v2\Articles__2_0.
 */

namespace Drupal\restful_example\Plugin\resource\node\article\v2;

use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\ResourceEntity;
use Drupal\restful\Plugin\resource\ResourceInterface;
use Drupal\restful\Plugin\resource\ResourceNode;

/**
 * Class Articles
 * @package Drupal\restful\Plugin\resource
 *
 * @Resource(
 *   name = "articles:2.0",
 *   resource = "articles",
 *   label = "Articles",
 *   description = "Export the article content type.",
 *   authenticationOptional = TRUE,
 *   dataProvider = {
 *     "entityType": "node",
 *     "bundles": {
 *       "article"
 *     },
 *   },
 *   majorVersion = 2,
 *   minorVersion = 0
 * )
 */
class Articles__2_0 extends ResourceNode implements ResourceInterface {}

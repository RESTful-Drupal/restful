<?php

/**
 * @file
 * Contains \Drupal\restful_example\Plugin\resource\node\article\v1\Articles__1_0.
 */

namespace Drupal\restful_example\Plugin\resource\node\article\v1;

use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\ResourceEntity;
use Drupal\restful\Plugin\resource\ResourceInterface;
use Drupal\restful\Plugin\resource\ResourceNode;

/**
 * Class Articles
 * @package Drupal\restful\Plugin\resource
 *
 * @Resource(
 *   name = "articles:1.0",
 *   resource = "articles",
 *   label = "Articles",
 *   description = "Export the articles with all authentication providers.",
 *   authenticationTypes = TRUE,
 *   authenticationOptional = TRUE,
 *   dataProvider = {
 *     "entityType": "node",
 *     "bundles": {
 *       "article"
 *     },
 *   },
 *   majorVersion = 1,
 *   minorVersion = 0
 * )
 */
class Articles__1_0 extends ResourceNode implements ResourceInterface {}

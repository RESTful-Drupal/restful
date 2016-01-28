<?php

/**
 * @file
 * Contains \Drupal\restful_token_auth_test\Plugin\resource\Articles__1_3.
 */

namespace Drupal\restful_token_auth_test\Plugin\resource;

use Drupal\restful\Plugin\resource\ResourceInterface;
use Drupal\restful\Plugin\resource\ResourceNode;

/**
 * Class Articles__1_3
 * @package Drupal\restful\Plugin\resource
 *
 * @Resource(
 *   name = "articles:1.3",
 *   resource = "articles",
 *   label = "Articles",
 *   description = "Export the articles with all authentication providers.",
 *   authenticationTypes = {
 *     "token"
 *   },
 *   dataProvider = {
 *     "entityType": "node",
 *     "bundles": {
 *       "article"
 *     },
 *   },
 *   majorVersion = 1,
 *   minorVersion = 3
 * )
 */
class Articles__1_3 extends ResourceNode implements ResourceInterface {}

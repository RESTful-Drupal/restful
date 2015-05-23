<?php

/**
 * @file
 * Contains \Drupal\restful_test\Plugin\resource\node\test_article\v1\TestArticles__1_0.
 */

namespace Drupal\restful_test\Plugin\resource\node\test_article\v1;

use Drupal\restful\Plugin\resource\ResourceInterface;
use Drupal\restful\Plugin\resource\ResourceNode;

/**
 * Class TestArticles__1_0
 * @package Drupal\restful\Plugin\resource
 *
 * @Resource(
 *   name = "test_articles:1.0",
 *   resource = "test_articles",
 *   label = "Test Articles",
 *   description = "Export the article content type.",
 *   authenticationTypes = {
 *     "basic_auth",
 *     "cookie"
 *   },
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
class TestArticles__1_0 extends ResourceNode implements ResourceInterface {}

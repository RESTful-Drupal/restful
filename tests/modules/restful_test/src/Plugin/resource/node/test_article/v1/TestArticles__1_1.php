<?php

/**
 * @file
 * Contains \Drupal\restful_test\Plugin\resource\node\test_article\v1\TestArticles__1_1.
 */

namespace Drupal\restful_test\Plugin\resource\node\test_article\v1;

use Drupal\restful\Plugin\resource\ResourceInterface;

/**
 * Class TestArticles
 * @package Drupal\restful\Plugin\resource
 *
 * @Resource(
 *   name = "test_articles:1.1",
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
 *     "sort": {
 *       "label": "ASC",
 *       "id": "DESC"
 *     },
 *   },
 *   renderCache = {
 *     "render": TRUE
 *   },
 *   majorVersion = 1,
 *   minorVersion = 1
 * )
 */
class TestArticles__1_1 extends TestArticles__1_0 implements ResourceInterface {
  // TODO: Document a changelog from defaultSortInfo to the annotation for the data provider.
}

<?php

/**
 * @file
 * Contains \Drupal\restful_test\Plugin\resource\node\test_article\v1\TestArticles__1_4.
 */

namespace Drupal\restful_test\Plugin\resource\node\test_article\v1;

use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\ResourceInterface;
use Drupal\restful\Plugin\resource\ResourceNode;

/**
 * Class TestArticles
 * @package Drupal\restful\Plugin\resource
 *
 * @Resource(
 *   name = "test_articles:1.4",
 *   resource = "test_articles",
 *   label = "Test Articles",
 *   description = "Export the article content type.",
 *   authenticationTypes = TRUE,
 *   authenticationOptional = TRUE,
 *   dataProvider = {
 *     "entityType": "node",
 *     "bundles": {
 *       "article"
 *     },
 *   },
 *   allowOrigin = "*",
 *   formatter = "hal_xml",
 *   majorVersion = 1,
 *   minorVersion = 4
 * )
 */
class TestArticles__1_4 extends ResourceNode implements ResourceInterface {

  /**
   * {@inheritdoc}
   */
  public function controllersInfo() {
    return array(
      '' => array(
        RequestInterface::METHOD_HEAD => 'index',
        RequestInterface::METHOD_OPTIONS => 'discover',
      ),
      '^(\d+,)*\d+$' => array(
        RequestInterface::METHOD_PATCH => 'update',
        RequestInterface::METHOD_DELETE => 'remove',
        RequestInterface::METHOD_OPTIONS => 'discover',
      ),
    );
  }

}

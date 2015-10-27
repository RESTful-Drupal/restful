<?php

/**
 * @file
 * Contains \Drupal\restful_test\Plugin\resource\node\test_article\v1\TestArticles__1_3.
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
 *   name = "test_articles:1.3",
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
 *   majorVersion = 1,
 *   minorVersion = 3
 * )
 */
class TestArticles__1_3 extends ResourceNode implements ResourceInterface {

  /**
   * {@inheritdoc}
   */
  public function controllersInfo() {
    $info = parent::controllersInfo();
    $info['^.*$'][RequestInterface::METHOD_GET] = array(
      'callback' => 'view',
      'access callback' => 'accessViewEntityFalse',
    );
    $info['^.*$'][RequestInterface::METHOD_HEAD] = array(
      'callback' => 'view',
      'access callback' => 'accessViewEntityTrue',
    );
    return $info;
  }

  /**
   * Custom access callback for the GET method.
   *
   * @return bool
   *   TRUE for access granted, FALSE otherwise.
   */
  public function accessViewEntityFalse() {
    return FALSE;
  }

  /**
   * Custom access callback for the HEAD method.
   *
   * @return bool
   *   TRUE for access granted, FALSE otherwise.
   */
  public function accessViewEntityTrue() {
    return TRUE;
  }

}

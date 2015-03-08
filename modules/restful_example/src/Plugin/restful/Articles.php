<?php

/**
 * @file
 * Contains \Drupal\restful_example\Plugin\restful\Articles__1_0.
 */

namespace Drupal\restful_example\Plugin\restful;

use Drupal\restful\Plugin\resource\ResourceEntity;
use Drupal\restful\Plugin\resource\ResourceInterface;

/**
 * Class Articles
 * @package Drupal\restful\Plugin\formatter
 *
 * @Formatter(
 *   name = "articles",
 *   resource = "articles",
 *   label = "Articles",
 *   description = "Export the article content type with "cookie" authentication.",
 *   authenticationTypes = TRUE,
 *   authenticationOptional = TRUE,
 *   dataProvider = {
 *     "entityType": "node",
 *     "bundle": "article",
 *   },
 *   majorVersion = 1,
 *   minorVersion = 0
 * )
 */
class Articles__1_0 extends ResourceEntity implements ResourceInterface {

  /**
   * Public fields.
   *
   * @return array
   *   The field definition array.
   */
  protected function publicFields() {
    return array(
      'id' => array(
        'wrapper_method' => 'getIdentifier',
        'wrapper_on_method' => TRUE,
      ),
      'self' => array(
        'callback' => array($this, 'getEntitySelf'),
      ),
    );
  }

}

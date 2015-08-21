<?php

/**
 * @file
 * Contains \Drupal\restful_example\Plugin\resource\node\article\v2\Articles__2_1.
 */

namespace Drupal\restful_example\Plugin\resource\node\article\v2;

use Drupal\restful\Plugin\resource\ResourceInterface;
use Drupal\restful\Plugin\resource\ResourceNode;

/**
 * Class Articles
 * @package Drupal\restful\Plugin\resource
 *
 * @Resource(
 *   name = "articles:2.1",
 *   resource = "articles",
 *   label = "Articles",
 *   description = "Export the article content type.",
 *   authenticationOptional = TRUE,
 *   dataProvider = {
 *     "entityType": "node",
 *     "bundles": {
 *       "article"
 *     },
 *     "idField": "custom-uuid"
 *   },
 *   formatter = "json_api",
 *   majorVersion = 2,
 *   minorVersion = 1
 * )
 */
class Articles__2_1 extends ResourceNode implements ResourceInterface {

  // TODO: Document the use of the idField.
  /**
   * {@inheritdoc}
   */
  protected function publicFields() {
    $fields = parent::publicFields();

    $fields['custom-uuid'] = array(
      'methods' => array(),
      'property' => 'uuid',
    );

    return $fields;
  }


}

<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Users__1_0.
 */

namespace Drupal\restful\Plugin\resource;

use Drupal\restful\Http\RequestInterface;

/**
 * Class Articles
 * @package Drupal\restful\Plugin\resource
 *
 * @Resource(
 *   name = "users:1.0",
 *   resource = "users",
 *   label = "Users",
 *   description = "Export the article content type with cookie authentication.",
 *   authenticationTypes = TRUE,
 *   authenticationOptional = TRUE,
 *   dataProvider = {
 *     "entityType": "user",
 *     "bundles": {
 *       "user"
 *     },
 *   },
 *   majorVersion = 1,
 *   minorVersion = 0
 * )
 */
class Users__1_0 extends ResourceEntity implements ResourceInterface {

  /**
   * {@inheritdoc}
   */
  protected function publicFields() {
    $public_fields = array();
    $public_fields['id'] = array(
      'property' => 'uid',
      'methods' => array(RequestInterface::METHOD_GET),
    );
    $public_fields['mail'] = array(
      'property' => 'mail',
    );
    $public_fields['self'] = array(
      'callback' => array($this, 'getEntitySelf'),
    );

    return $public_fields;
  }

}

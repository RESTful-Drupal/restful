<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Users__1_0.
 */

namespace Drupal\restful\Plugin\resource;

/**
 * Class Articles
 * @package Drupal\restful\Plugin\resource
 *
 * @Resource(
 *   name = "users:1.0",
 *   resource = "users",
 *   label = "Users",
 *   description = "Export the User entity with cookie authentication.",
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
    $public_fields = parent::publicFields();

    // The mail will be shown only to the own user or privileged users.
    $public_fields['mail'] = array(
      'property' => 'mail',
    );

    return $public_fields;
  }

}

<?php

/**
 * @file
 * Contains \Drupal\restful_example\Plugin\resource\Tags__1_0.
 */

namespace Drupal\restful_example\Plugin\resource;

use Drupal\restful\Plugin\resource\ResourceEntity;
use Drupal\restful\Plugin\resource\ResourceInterface;

/**
 * Class Tags
 * @package Drupal\restful\Plugin\resource
 *
 * @Resource(
 *   name = "tags:1.0",
 *   resource = "tags",
 *   label = "Tags",
 *   description = "Export the tags taxonomy term.",
 *   authenticationTypes = TRUE,
 *   authenticationOptional = TRUE,
 *   dataProvider = {
 *     "entityType": "taxonomy_term",
 *     "bundles": {
 *       "tags"
 *     },
 *   },
 *   majorVersion = 1,
 *   minorVersion = 0
 * )
 */
class Tags__1_0 extends ResourceEntity implements ResourceInterface {

  /**
   * {@inheritdoc}
   */
  protected function publicFields() {
    return array(
      'id' => array(
        'wrapper_method' => 'getIdentifier',
        'wrapper_method_on_entity' => TRUE,
      ),
      'label' => array(
        'wrapper_method' => 'label',
        'wrapper_method_on_entity' => TRUE,
      ),
      'self' => array(
        'callback' => array($this, 'getEntitySelf'),
      ),
    );
  }

}

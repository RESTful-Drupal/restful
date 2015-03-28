<?php

/**
 * @file
 * Contains \Drupal\restful_example\Plugin\resource\Articles__1_0.
 */

namespace Drupal\restful_example\Plugin\resource;

use Drupal\restful\Http\Request;
use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\ResourceEntity;
use Drupal\restful\Plugin\resource\ResourceInterface;

/**
 * Class Articles
 * @package Drupal\restful\Plugin\resource
 *
 * @Resource(
 *   name = "articles:1.0",
 *   resource = "articles",
 *   label = "Articles",
 *   description = "Export the article content type with cookie authentication.",
 *   authenticationTypes = TRUE,
 *   authenticationOptional = TRUE,
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
class Articles__1_0 extends ResourceEntity implements ResourceInterface {

  /**
   * {@inheritdoc}
   */
  protected function publicFields() {
    $public_fields = array();
    $public_fields['id'] = array(
      'property' => 'nid',
    );
    $public_fields['label'] = array(
      'wrapper_method' => 'label',
      'wrapper_method_on_entity' => TRUE,
      'process_callbacks' => array(
        array(array($this, 'addPrefix'), array('Label: ')),
      ),
    );
    $public_fields['self'] = array(
      'callback' => array($this, 'getEntitySelf'),
    );
    $public_fields['tags'] = array(
      'property' => 'field_tags',
      'resource' => array(
        'name' => 'tags',
        'majorVersion' => 1,
        'minorVersion' => 0,
      ),
    );
    $public_fields['status'] = array(
      'property' => 'status',
      'methods' => array(RequestInterface::METHOD_GET),
    );
    $public_fields['body'] = array(
      'property' => 'body',
      'formatter' => array(
        'type' => 'text_summary_or_trimmed',
        'settings' => array(
          'trim_length' => 100,
        ),
      ),
    );

    return $public_fields;
  }

  /**
   * Helper function that adds a prefix.
   *
   * @param mixed $value
   *   The input value to be prefixed.
   * @param string $prefix
   *   The prefix to add.
   *
   * @return string
   *   The prefixed value.
   */
  public function addPrefix($value, $prefix) {
    return $prefix . $value;
  }

}

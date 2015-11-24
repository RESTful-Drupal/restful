<?php

/**
 * @file
 * Contains \Drupal\restful_example\Plugin\resource\comment\Comments__1_0.
 */

namespace Drupal\restful_example\Plugin\resource\comment;

use Drupal\restful\Plugin\resource\ResourceEntity;
use Drupal\restful\Plugin\resource\ResourceInterface;

/**
 * Class Comments__1_0
 * @package Drupal\restful_example\Plugin\resource\comment
 *
 * @Resource(
 *   name = "comments:1.0",
 *   resource = "comments",
 *   label = "Comments",
 *   description = "Export the comments with all authentication providers.",
 *   authenticationTypes = TRUE,
 *   authenticationOptional = TRUE,
 *   dataProvider = {
 *     "entityType": "comment",
 *     "bundles": FALSE,
 *   },
 *   majorVersion = 1,
 *   minorVersion = 0
 * )
 */
class Comments__1_0 extends ResourceEntity implements ResourceInterface {

  /**
   * {@inheritdoc}
   */
  protected function publicFields() {
    $public_fields = parent::publicFields();

    $public_fields['nid'] = array(
      'property' => 'node',
      'sub_property' => 'nid',
    );

    // Add a custom field for test only.
    if (field_info_field('comment_text')) {
      $public_fields['comment_text'] = array(
        'property' => 'comment_text',
        'sub_property' => 'value',
      );
    }

    return $public_fields;
  }

  /**
   * {@inheritdoc}
   */
  protected function dataProviderClassName() {
    return '\Drupal\restful_example\Plugin\resource\comment\DataProviderComment';
  }

}

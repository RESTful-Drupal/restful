<?php

/**
 * @file
 * Contains \Drupal\restful_example\Plugin\resource\node\article\v1\Articles__1_5.
 */

namespace Drupal\restful_example\Plugin\resource\node\article\v1;

use Drupal\restful\Plugin\resource\DataInterpreter\DataInterpreterInterface;
use Drupal\restful\Plugin\resource\Field\ResourceFieldBase;
use Drupal\restful\Plugin\resource\ResourceInterface;
use Drupal\restful\Plugin\resource\ResourceNode;

/**
 * Class Articles__1_5
 * @package Drupal\restful\Plugin\resource
 *
 * @Resource(
 *   name = "articles:1.5",
 *   resource = "articles",
 *   label = "Articles",
 *   description = "Export the articles with all authentication providers.",
 *   authenticationTypes = TRUE,
 *   authenticationOptional = TRUE,
 *   dataProvider = {
 *     "entityType": "node",
 *     "bundles": {
 *       "article"
 *     },
 *   },
 *   majorVersion = 1,
 *   minorVersion = 5
 * )
 */
class Articles__1_5 extends ResourceNode implements ResourceInterface {

  /**
   * Overrides ResourceNode::publicFields().
   */
  protected function publicFields() {
    $public_fields = parent::publicFields();

    $public_fields['body'] = array(
      'property' => 'body',
      'sub_property' => 'value',
    );

    $public_fields['tags'] = array(
      'property' => 'field_tags',
      'resource' => array(
        'name' => 'tags',
        'majorVersion' => 1,
        'minorVersion' => 0,
      ),
    );

    $public_fields['image'] = array(
      'property' => 'field_image',
      'process_callbacks' => array(
        array($this, 'imageProcess'),
      ),
      'image_styles' => array('thumbnail', 'medium', 'large'),
    );

    // By checking that the field exists, we allow re-using this class on
    // different tests, where different fields exist.
    if (field_info_field('field_images')) {
      $public_fields['images'] = array(
        'property' => 'field_images',
        'process_callbacks' => array(
          array($this, 'imageProcess'),
        ),
        'image_styles' => array('thumbnail', 'medium', 'large'),
      );
    }

    $public_fields['user'] = array(
      'property' => 'author',
      'resource' => array(
        // The name of the resource to map to.
        'name' => 'users',
        // Determines if the entire resource should appear, or only the ID.
        'fullView' => TRUE,
        'majorVersion' => 1,
        'minorVersion' => 0,
      ),
    );

    $public_fields['static'] = array(
      'callback' => '\Drupal\restful_example\Plugin\resource\node\article\v1\Articles__1_5::randomNumber',
    );

    return $public_fields;
  }

  /**
   * Process callback, Remove Drupal specific items from the image array.
   *
   * @param array $value
   *   The image array.
   *
   * @return array
   *   A cleaned image array.
   */
  public function imageProcess($value) {
    if (ResourceFieldBase::isArrayNumeric($value)) {
      $output = array();
      foreach ($value as $item) {
        $output[] = $this->imageProcess($item);
      }
      return $output;
    }
    return array(
      'id' => $value['fid'],
      'self' => file_create_url($value['uri']),
      'filemime' => $value['filemime'],
      'filesize' => $value['filesize'],
      'width' => $value['width'],
      'height' => $value['height'],
      'styles' => $value['image_styles'],
    );
  }

  /**
   * Callback, Generate a random number.
   *
   * @param DataInterpreterInterface $interpreter
   *   The data interpreter containing the wrapper.
   *
   * @return int
   *   A random integer.
   */
  public static function randomNumber(DataInterpreterInterface $interpreter) {
    return mt_rand();
  }

}

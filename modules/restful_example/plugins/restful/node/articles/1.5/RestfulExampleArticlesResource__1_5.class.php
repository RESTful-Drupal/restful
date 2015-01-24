<?php

/**
 * @file
 * Contains RestfulExampleArticlesResource__1_5.
 */

class RestfulExampleArticlesResource__1_5 extends RestfulEntityBaseNode {

  /**
   * Overrides RestfulExampleArticlesResource::publicFieldsInfo().
   */
  public function publicFieldsInfo() {
    $public_fields = parent::publicFieldsInfo();

    $public_fields['body'] = array(
      'property' => 'body',
      'sub_property' => 'value',
    );

    $public_fields['tags'] = array(
      'property' => 'field_tags',
      'resource' => array(
        'tags' => 'tags',
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
        // The bundle of the entity.
        'user' => array(
          // The name of the resource to map to.
          'name' => 'users',
          // Determines if the entire resource should appear, or only the ID.
          'full_view' => TRUE,
        ),
      ),
    );

    $public_fields['static'] = array(
      'callback' => 'static::randomNumber',
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
  protected function imageProcess($value) {
    if (static::isArrayNumeric($value)) {
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
   * @param \EntityMetadataWrapper $wrapper
   *   The EMW.
   *
   * @return int
   *   A random integer.
   */
  public static function randomNumber($wrapper) {
    return mt_rand();
  }

}

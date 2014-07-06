<?php

/**
 * @file
 * Contains RestfulExampleArticlesResource.
 */

class RestfulExampleArticlesResource extends RestfulEntityBaseNode {

  /**
   * Overrides RestfulExampleArticlesResource::getPublicFields().
   */
  public function getPublicFields() {
    $public_fields = parent::getPublicFields();

    $public_fields['body'] = array(
      'property' => 'body',
      'sub_property' => 'value',
    );

    $public_fields['image'] = array(
      'property' => 'field_image',
    );

    return $public_fields;
  }
}

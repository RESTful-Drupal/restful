<?php

/**
 * @file
 * Contains RestfulExampleArticlesResource.
 */

class RestfulExampleArticlesResource extends RestfulEntityBaseNode {

  public function getPublicFields() {
    $public_fields = parent::getPublicFields();
    $public_fields['er'] = array(
      'property' => 'field_er',
      'resource' => array(
        'article' => 'articles',
      ),
    );
    return $public_fields;
  }
}

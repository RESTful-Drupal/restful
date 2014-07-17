<?php

/**
 * @file
 * Contains RestfulTestArticlesResource__1_2.
 */

class RestfulTestArticlesResource__1_2 extends RestfulEntityBaseNode {

  /**
   * Overrides \RestfulEntityBase::getPublicFields().
   */
  public function getPublicFields() {
    $public_fields = parent::getPublicFields();

    $public_fields['entity_reference_single'] = array(
      'property' => 'entity_reference_single',
      'resource' => array(
        'article' => 'test_articles',
      ),
    );

    $public_fields['entity_reference_multiple'] = array(
      'property' => 'entity_reference_multiple',
      'resource' => array(
        'article' => 'test_articles',
      ),
    );


    return $public_fields;
  }

}

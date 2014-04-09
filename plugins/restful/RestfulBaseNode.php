<?php


/**
 * @file
 * Contains RestfulBaseNode.
 */

/**
 * A base implementation for "Node" entity type.
 */
class RestfulBaseNode extends RestfulBase {

  /**
   * Overrides RestfulBase::getPublicFields().
   */
  public function getPublicFields() {
    $public_fields = parent::getPublicFields();
    $public_fields['label']['property'] = 'title';
    return $public_fields;
  }
}

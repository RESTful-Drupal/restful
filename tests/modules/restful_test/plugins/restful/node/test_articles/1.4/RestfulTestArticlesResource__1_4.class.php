<?php

/**
 * @file
 * Contains RestfulTestArticlesResource__1_4.
 */

class RestfulTestArticlesResource__1_4 extends RestfulEntityBaseNode {

  /**
   * Overrides \RestfulEntityBase::controllersInfo().
   */
  public static function controllersInfo() {
    return array(
      '' => array(
        \RestfulInterface::HEAD => 'getList',
      ),
      '^(\d+,)*\d+$' => array(
        \RestfulInterface::PATCH => 'patchEntity',
        \RestfulInterface::DELETE => 'deleteEntity',
      ),
    );
  }

}

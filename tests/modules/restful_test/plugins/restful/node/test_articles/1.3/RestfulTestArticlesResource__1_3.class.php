<?php

/**
 * @file
 * Contains RestfulExampleArticlesResource__1_3.
 */

class RestfulTestArticlesResource__1_3 extends RestfulEntityBaseNode {

  /**
   * {@inheritdoc}
   */
  public static function controllersInfo() {
    $info = parent::controllersInfo();
    $info['^.*$'][\RestfulInterface::GET] = array(
      'callback' => 'viewEntities',
      'access callback' => 'accessViewEntityFalse',
    );
    $info['^.*$'][\RestfulInterface::HEAD] = array(
      'callback' => 'viewEntities',
      'access callback' => 'accessViewEntityTrue',
    );
    return $info;
  }

  /**
   * Custom access callback for the GET method.
   *
   * @return boolean
   *   TRUE for access granted, FALSE otherwise.
   */
  protected function accessViewEntityFalse() {
    return FALSE;
  }

  /**
   * Custom access callback for the HEAD method.
   *
   * @return boolean
   *   TRUE for access granted, FALSE otherwise.
   */
  protected function accessViewEntityTrue() {
    return TRUE;
  }

}

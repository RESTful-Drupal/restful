<?php

/**
 * @file
 * Contains RestfulExampleArticlesResource__1_7.
 */

class RestfulExampleArticlesResource__1_7 extends RestfulEntityBaseNode {

  /**
   * {@inheritdoc}
   */
  public static function controllersInfo() {
    return array(
      '' => array(
        // GET returns a list of entities.
        \RestfulInterface::GET => 'getList',
        \RestfulInterface::HEAD => 'getList',
        // POST
        \RestfulInterface::POST => 'createEntity',
      ),
      '^(\d+,)*\d+$' => array(
        \RestfulInterface::GET => array(
          'callback' => 'viewEntities',
          'access callback' => 'accessViewEntityFalse',
        ),
        \RestfulInterface::HEAD => array(
          'callback' => 'viewEntities',
          'access callback' => 'accessViewEntityTrue',
        ),
        \RestfulInterface::PUT => 'putEntity',
        \RestfulInterface::PATCH => 'patchEntity',
        \RestfulInterface::DELETE => 'deleteEntity',
      ),
    );
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
   * Custom access callback for the GET method.
   *
   * @return boolean
   *   TRUE for access granted, FALSE otherwise.
   */
  protected function accessViewEntityTrue() {
    return TRUE;
  }

}

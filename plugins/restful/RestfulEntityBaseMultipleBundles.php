<?php

/**
 * @file
 * Contains RestfulEntityBaseMultipleBundles.
 */

class RestfulEntityBaseMultipleBundles extends RestfulEntityBase {

  /**
   * Define the bundles to expose to the API.
   *
   * Bundle => Resource
   */
  protected $bundles = array();

  protected $controllers = array(
    '' => array(
      // GET returns a list of entities.
      'get' => 'getList',
    ),
  );

  public function __construct($plugin) {
    parent::__construct($plugin);

    $this->bundles = $plugin['bundles'];
  }

  /**
   * Return the bundles.
   *
   * @return array
   *  An array of the exposed bundles.
   */
  protected function getBundles() {
    return $this->bundles;
  }

  /**
   * Get a list of entities.
   *
   * @param null $request
   *   (optional) The request.
   * @param null $account
   *   (optional) The user object.
   *
   * @return array
   *   Array of entities, as passed to RestfulEntityBase::getView().
   *
   * @throws RestfulBadRequestException
   */
  public function getList($request, $account) {
    $handlers = array();
    $return = array();
    foreach ($this->getBundles() as $bundle => $resource) {
      $handlers[$bundle] = restful_get_restful_handler($resource);

      $results = $handlers[$bundle]->getList($request, $account);
      $return['list'][$bundle] = $results['list'];
    }

    return $return;
  }

}

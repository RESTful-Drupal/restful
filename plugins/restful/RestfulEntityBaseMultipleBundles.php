<?php

/**
 * @file
 * Contains RestfulEntityBaseMultipleBundles.
 */

class RestfulEntityBaseMultipleBundles extends RestfulEntityBase {

  /**
   * Define the bundles to exposed to the API.
   *
   * @var array
   *  Array keyed by bundle machine, and the RESTful resource as the value.
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
   *  An array of the exposed bundles as key and resource as value.
   */
  protected function getBundles() {
    return $this->bundles;
  }

  /**
   * Overrides RestfulEntityBase::getQueryForList().
   */
  public function getQueryForList($request, $account) {
    $query = parent::getQueryForList($request, $account);
    $query->entityCondition('bundle', array_keys($this->getBundles()), 'IN');
    return $query;
  }
}

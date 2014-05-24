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
   * Overrides RestfulEntityBase::getList().
   */
  public function getList($request = NULL, stdClass $account = NULL) {
    $entity_type = $this->entityType;
    $result = $this
      ->getQueryForList($request, $account)
      ->execute();


    if (empty($result[$entity_type])) {
      return;
    }

    $ids = array_keys($result[$entity_type]);

    // Pre-load all entities.
    $entities = entity_load($entity_type, $ids);

    $return = array('list' => array());

    $handlers = array();
    $resources_info = $this->getBundles();

    foreach ($entities as $entity) {
      // Call each handler by its registered bundle.
      list($id,, $bundle) = entity_extract_ids($this->getEntityType(), $entity);
      if (empty($handlers[$bundle])) {
        $version = $this->getVersion();
        $handlers[$bundle] = restful_get_restful_handler($resources_info[$bundle], $version['major'], $version['minor']);
      }

      $bundle_handler = $handlers[$bundle];
      $return['list'][] = $bundle_handler->viewEntity($id, $request, $account);
    }

    return $return;
  }

  /**
   * Overrides RestfulEntityBase::getQueryForList().
   */
  public function getQueryForList($request, stdClass $account = NULL) {
    $query = parent::getQueryForList($request, $account);
    $query->entityCondition('bundle', array_keys($this->getBundles()), 'IN');
    return $query;
  }
}

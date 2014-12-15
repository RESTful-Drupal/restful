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

  /**
   * Overrides \RestfulBase::controllersInfo().
   */
  public static function controllersInfo() {
    return array(
      '' => array(
        // GET returns a list of entities.
        \RestfulInterface::GET => 'getList',
      ),
    );
  }

  public function __construct(array $plugin, \RestfulAuthenticationManager $auth_manager = NULL, \DrupalCacheInterface $cache_controller = NULL, $language = NULL) {
    parent::__construct($plugin, $auth_manager, $cache_controller, $language);

    if (!empty($plugin['bundles'])) {
      $this->bundles = $plugin['bundles'];
    }
    $this->authenticationManager = $auth_manager ? $auth_manager : new \RestfulAuthenticationManager();
    $this->cacheController = $cache_controller ? $cache_controller : $this->newCacheObject();
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
  public function getList() {
    $entity_type = $this->entityType;
    $result = $this
      ->getQueryForList()
      ->execute();


    if (empty($result[$entity_type])) {
      return;
    }

    $account = $this->getAccount();
    $request = $this->getRequest();

    $ids = array_keys($result[$entity_type]);

    // Pre-load all entities.
    $entities = entity_load($entity_type, $ids);

    $return = array();

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
      $bundle_handler->setAccount($account);
      $bundle_handler->setRequest($request);
      $return[] = $bundle_handler->viewEntity($id);
    }

    return $return;
  }

  /**
   * Overrides RestfulEntityBase::getQueryForList().
   */
  public function getQueryForList() {
    $query = parent::getQueryForList();
    $query->entityCondition('bundle', array_keys($this->getBundles()), 'IN');
    return $query;
  }
}

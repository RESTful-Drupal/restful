<?php

/**
 * @file
 * Contains \RestfulDataProviderEFQ
 */

use Drupal\restful\Authentication\AuthenticationManager;
use Drupal\restful\Exception\BadRequestException;
use Drupal\restful\Exception\ForbiddenException;
use Drupal\restful\Exception\ServerConfigurationException;

abstract class RestfulDataProviderEFQ extends \RestfulBase implements \RestfulDataProviderEFQInterface, \RestfulDataProviderInterface {

  /**
   * The entity type.
   *
   * @var string
   */
  protected $entityType;

  /**
   * The bundle.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The bundle.
   *
   * @var string
   */
  protected $EFQClass = '\EntityFieldQuery';

  /**
   * Getter for $bundle.
   *
   * @return string
   */
  public function getBundle() {
    return $this->bundle;
  }

  /**
   * Getter for $entityType.
   *
   * @return string
   */
  public function getEntityType() {
    return $this->entityType;
  }

  /**
   * Get the entity info for the current entity the endpoint handling.
   *
   * @param null $type
   *   The entity type. Optional.
   * @return array
   *   The entity info.
   */
  public function getEntityInfo($type = NULL) {
    return entity_get_info($type ? $type : $this->getEntityType());
  }

  /**
   * Constructs a RestfulDataProviderEFQ object.
   *
   * @param array $plugin
   *   Plugin definition.
   * @param AuthenticationManager $auth_manager
   *   (optional) Injected authentication manager.
   * @param DrupalCacheInterface $cache_controller
   *   (optional) Injected cache backend.
   * @param string $language
   *   (optional) The language to return items in.
   *
   * @throws ServerConfigurationException
   */
  public function __construct(array $plugin, AuthenticationManager $auth_manager = NULL, \DrupalCacheInterface $cache_controller = NULL, $language = NULL) {
    parent::__construct($plugin, $auth_manager, $cache_controller, $language);
    $this->entityType = $plugin['entity_type'];
    $this->bundle = $plugin['bundle'];

    // Allow providing an alternative to \EntityFieldQuery.
    $data_provider_options = $this->getPluginKey('data_provider_options');
    if (!empty($data_provider_options['efq_class'])) {
      if (!is_subclass_of($data_provider_options['efq_class'], '\EntityFieldQuery')) {
        throw new ServerConfigurationException(format_string('The provided class @class does not extend from \EntityFieldQuery.', array(
          '@class' => $data_provider_options['efq_class'],
        )));
      }
      $this->EFQClass = $data_provider_options['efq_class'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryForList() {
    $entity_type = $this->getEntityType();
    $query = $this->getEntityFieldQuery();
    if ($path = $this->getPath()) {
      $ids = explode(',', $path);
      if (!empty($ids)) {
        $query->entityCondition('entity_id', $ids, 'IN');
      }
    }

    $this->queryForListSort($query);
    $this->queryForListFilter($query);
    $this->queryForListPagination($query);
    $this->addExtraInfoToQuery($query);

    return $query;
  }


  /**
   * Get the DB column name from a property.
   *
   * The "property" defined in the public field is actually the property
   * of the entity metadata wrapper. Sometimes that property can be a
   * different name than the column in the DB. For example, for nodes the
   * "uid" property is mapped in entity metadata wrapper as "author", so
   * we make sure to get the real column name.
   *
   * @param string $property_name
   *   The property name.
   *
   * @return string
   *   The column name.
   */
  protected function getColumnFromProperty($property_name) {
    $property_info = entity_get_property_info($this->getEntityType());
    return $property_info['properties'][$property_name]['schema field'];
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryCount() {
    $query = $this->getEntityFieldQuery();
    if ($path = $this->getPath()) {
      $ids = explode(',', $path);
      $query->entityCondition('entity_id', $ids, 'IN');
    }

    $this->addExtraInfoToQuery($query);
    $query->addTag('restful_count');

    $this->queryForListFilter($query);

    return $query->count();
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalCount() {
    return intval($this
      ->getQueryCount()
      ->execute());
  }

  /**
   * {@inheritdoc}
   */
  public function index() {
    // Defer the actual implementation to \RestfulEntityBase.
    return $this->getList();
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $ids) {
    // Defer the actual implementation to \RestfulEntityBase.
    return $this->viewEntities(implode(',', $ids));
  }

  /**
   * {@inheritdoc}
   */
  public function view($id) {
    // Defer the actual implementation to \RestfulEntityBase.
    return $this->viewEntity($id);
  }

  /**
   * {@inheritdoc}
   */
  public function update($id, $full_replace = FALSE) {
    // Defer the actual implementation to \RestfulEntityBase.
    return $this->updateEntity($id, $full_replace);
  }

  /**
   * {@inheritdoc}
   */
  public function create() {
    // Defer the actual implementation to \RestfulEntityBase.
    return $this->createEntity();
  }

  /**
   * {@inheritdoc}
   */
  public function remove($id) {
    // Defer the actual implementation to \RestfulEntityBase.
    $this->deleteEntity($id);
  }

  /**
   * Get a list of entities.
   *
   * @return array
   *   Array of entities, as passed to RestfulEntityBase::viewEntity().
   *
   * @throws BadRequestException
   */
  abstract public function getList();

  /**
   * View an entity.
   *
   * @param $id
   *   The ID to load the entity.
   *
   * @return array
   *   Array with the public fields populated.
   *
   * @throws Exception
   */
  abstract public function viewEntity($id);

  /**
   * Get a list of entities based on a list of IDs.
   *
   * @param string $ids_string
   *   Coma separated list of ids.
   *
   * @return array
   *   Array of entities, as passed to RestfulEntityBase::viewEntity().
   *
   * @throws BadRequestException
   */
  abstract public function viewEntities($ids_string);

  /**
   * Create a new entity.
   *
   * @return array
   *   Array with the output of the new entity, passed to
   *   RestfulEntityInterface::viewEntity().
   *
   * @throws ForbiddenException
   */
  abstract public function createEntity();

  /**
   * Update an entity.
   *
   * @param $id
   *   The ID to load the entity.
   * @param bool $null_missing_fields
   *   Determine if properties that are missing form the request array should
   *   be treated as NULL, or should be skipped. Defaults to FALSE, which will
   *   skip missing the fields to NULL.
   *
   * @return array
   *   Array with the output of the new entity, passed to
   *   RestfulEntityInterface::viewEntity().
   */
  abstract protected function updateEntity($id, $null_missing_fields = FALSE);

  /**
   * Delete an entity using DELETE.
   *
   * No result is returned, just the HTTP header is set to 204.
   *
   * @param $id
   *   The ID to load the entity.
   */
  abstract public function deleteEntity($id);


}

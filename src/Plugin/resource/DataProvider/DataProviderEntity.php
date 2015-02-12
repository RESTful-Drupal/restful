<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\DataProvider\DataProviderEntity.
 */

namespace Drupal\restful\Plugin\resource\DataProvider;

use Drupal\restful\Exception\ServerConfigurationException;
use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\Field\ResourceFieldCollectionInterface;
use Drupal\restful\Plugin\resource\Field\ResourceFieldEntityInterface;

use Drupal\restful\Exception\BadRequestException;
use Drupal\restful\Exception\UnprocessableEntityException;

class DataProviderEntity extends DataProvider {

  // TODO: The Data Provider should be in charge of the entity_access checks.

  /**
   * Field definitions.
   *
   * A collection of \Drupal\restful\Plugin\resource\Field\ResourceFieldEntityInterface[].
   *
   * @var ResourceFieldCollectionInterface
   */
  protected $fieldDefinitions;

  /**
   * The entity type.
   *
   * @var string
   */
  protected $entityType;

  /**
   * The entity bundles.
   *
   * @var array
   */
  protected $bundles = array();

  /**
   * The entity field query class.
   *
   * @var string
   */
  protected $EFQClass;

  /**
   * Constructor.
   *
   * @param RequestInterface $request
   *   The request.
   * @param string $entity_type
   *   The entity type for this data provider.
   * @param array $bundles
   *   The bundles for this data provider.
   * @param ResourceFieldCollectionInterface $field_definitions
   *   The field definitions.
   * @param string $efq_class
   *   The class to instantiate EFQ objects.
   *
   * @throws ServerConfigurationException
   *  If the field mappings are not for entities.
   */
  public function __construct(RequestInterface $request, $entity_type, array $bundles, ResourceFieldCollectionInterface $field_definitions, $efq_class) {
    $this->request = $request;
    $this->entityType = $entity_type;
    $this->bundles = $bundles;
    // Make sure that all field definitions are instance of
    // \Drupal\restful\Plugin\resource\Field\ResourceFieldEntityInterface
    foreach ($field_definitions as $key => $value) {
      if (!($value instanceof ResourceFieldEntityInterface)) {
        throw new ServerConfigurationException('The field mapping must implement \Drupal\restful\Plugin\resource\Field\ResourceFieldEntityInterface.');
      }
    }
    $this->fieldDefinitions = $field_definitions;
    $this->EFQClass = $efq_class;
  }

  /**
   * {@inheritdoc}
   */
  public function index() {
    // TODO: Continue HERE.
  }

  /**
   * {@inheritdoc}
   */
  public function create($object) {

  }

  /**
   * {@inheritdoc}
   */
  public function view($subject) {
    $id = $subject;
    $entity_id = $this->getEntityIdByFieldId($id);

    if (!$this->isValidEntity('view', $entity_id)) {
      return;
    }


  }

  /**
   * {@inheritdoc}
   */
  public function viewEntity($id) {
    $entity_id = $this->getEntityIdByFieldId($id);
    $request = $this->getRequest();

    $cached_data = $this->getRenderedCache(array(
      'et' => $this->getEntityType(),
      'ei' => $entity_id,
    ));
    if (!empty($cached_data->data)) {
      return $cached_data->data;
    }

    if (!$this->isValidEntity('view', $entity_id)) {
      return;
    }

    $wrapper = entity_metadata_wrapper($this->entityType, $entity_id);
    $wrapper->language($this->getLangCode());
    $values = array();

    $limit_fields = !empty($request['fields']) ? explode(',', $request['fields']) : array();

    foreach ($this->getPublicFields() as $public_field_name => $info) {
      if ($limit_fields && !in_array($public_field_name, $limit_fields)) {
        // Limit fields doesn't include this property.
        continue;
      }

      $value = NULL;

      if ($info['create_or_update_passthrough']) {
        // The public field is a dummy one, meant only for passing data upon
        // create or update.
        continue;
      }

      if ($info['callback']) {
        $value = static::executeCallback($info['callback'], array($wrapper));
      }
      else {
        // Exposing an entity field.
        $property = $info['property'];
        $sub_wrapper = $info['wrapper_method_on_entity'] ? $wrapper : $wrapper->{$property};

        // Check user has access to the property.
        if ($property && !$this->checkPropertyAccess('view', $public_field_name, $sub_wrapper, $wrapper)) {
          continue;
        }

        if (empty($info['formatter'])) {
          if ($sub_wrapper instanceof EntityListWrapper) {
            // Multiple values.
            foreach ($sub_wrapper as $item_wrapper) {
              $value[] = $this->getValueFromProperty($wrapper, $item_wrapper, $info, $public_field_name);
            }
          }
          else {
            // Single value.
            $value = $this->getValueFromProperty($wrapper, $sub_wrapper, $info, $public_field_name);
          }
        }
        else {
          // Get value from field formatter.
          $value = $this->getValueFromFieldFormatter($wrapper, $sub_wrapper, $info);
        }
      }

      if ($value && $info['process_callbacks']) {
        foreach ($info['process_callbacks'] as $process_callback) {
          $value = static::executeCallback($process_callback, array($value));
        }
      }

      $values[$public_field_name] = $value;
    }

    $this->setRenderedCache($values, array(
      'et' => $this->getEntityType(),
      'ei' => $entity_id,
    ));
    return $values;
  }


  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $subjects) {

  }

  /**
   * {@inheritdoc}
   */
  public function update($subject, $object, $replace = TRUE) {

  }

  /**
   * {@inheritdoc}
   */
  public function remove($subject) {

  }

  /**
   * Get the entity ID based on the ID provided in the request.
   *
   * As any field may be used as the ID, we convert it to the numeric internal
   * ID of the entity
   *
   * @param mixed $id
   *   The provided ID.
   *
   * @throws BadRequestException
   * @throws UnprocessableEntityException
   *
   * @return int
   *   The entity ID.
   */
  protected function getEntityIdByFieldId($id) {
    $request = $this->getRequest();
    if (empty($request['loadByFieldName'])) {
      // The regular entity ID was provided.
      return $id;
    }
    $public_property_name = $request['loadByFieldName'];
    // We need to get the internal field/property from the public name.
    if ((!$public_field_info = $this->fieldDefinitions[$public_property_name]) || $public_field_info->getProperty()) {
      throw new BadRequestException(format_string('Cannot load an entity using the field "@name"', array(
        '@name' => $public_property_name,
      )));
    }
    $query = $this->getEntityFieldQuery();
    $query->range(0, 1);
    // Find out if the provided ID is a Drupal field or an entity property.
    if (static::propertyIsField($public_field_info['property'])) {
      $query->fieldCondition($public_field_info['property'], $public_field_info['column'], $id);
    }
    else {
      $query->propertyCondition($public_field_info['property'], $id);
    }

    // Execute the query and gather the results.
    $result = $query->execute();
    if (empty($result[$this->getEntityType()])) {
      throw new UnprocessableEntityException(format_string('The entity ID @id by @name for @resource cannot be loaded.', array(
        '@id' => $id,
        '@resource' => $this->getPluginKey('label'),
        '@name' => $public_property_name,
      )));
    }

    // There is nothing that guarantees that there is only one result, since
    // this is user input data. Return the first ID.
    $entity_id = key($result[$this->getEntityType()]);

    // REST requires a canonical URL for every resource.
    $this->addHttpHeaders('Link', $this->versionedUrl($entity_id, array(), FALSE) . '; rel="canonical"');

    return $entity_id;
  }

  /**
   * Initialize an EntityFieldQuery (or extending class).
   *
   * @return \EntityFieldQuery
   *   The initialized query with the basics filled in.
   */
  protected function getEntityFieldQuery() {
    $query = $this->EFQObject();
    $entity_type = $this->entityType;
    $query->entityCondition('entity_type', $entity_type);
    $entity_info = $this->getEntityInfo();
    if (!empty($this->bundles) && $entity_info['entity keys']['bundle']) {
      $query->entityCondition('bundle', $this->bundles, 'IN');
    }
    return $query;
  }

  /**
   * Gets a EFQ object.
   *
   * @return \EntityFieldQuery
   *   The object that inherits from \EntityFieldQuery.
   */
  protected function EFQObject() {
    $efq_class = $this->EFQClass;
    return new $efq_class();
  }

  /**
   * Get the entity info for the current entity the endpoint handling.
   *
   * @param string $type
   *   Optional. The entity type.
   *
   * @return array
   *   The entity info.
   *
   * @see entity_get_info().
   */
  protected function getEntityInfo($type = NULL) {
    return entity_get_info($type ? $type : $this->entityType);
  }

}

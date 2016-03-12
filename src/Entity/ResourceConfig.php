<?php

/**
 * @file
 * Contains \Drupal\restful\Entity\ResourceConfig.
 */

namespace Drupal\restful\Entity;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\restful\ResourceConfigInterface;

/**
 * Defines the Resource Config entity.
 *
 * @ConfigEntityType(
 *   id = "resource_config",
 *   label = @Translation("Resource Config"),
 *   handlers = {
 *     "list_builder" = "Drupal\restful\ResourceConfigListBuilder",
 *     "form" = {
 *       "add" = "Drupal\restful\Form\ResourceConfigForm",
 *       "edit" = "Drupal\restful\Form\ResourceConfigForm",
 *       "delete" = "Drupal\restful\Form\ResourceConfigDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\restful\ResourceConfigHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "resource_config",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/services/resource_config/{resource_config}",
 *     "add-form" = "/admin/config/services/resource_config/add",
 *     "edit-form" = "/admin/config/services/resource_config/{resource_config}/edit",
 *     "delete-form" = "/admin/config/services/resource_config/{resource_config}/delete",
 *     "collection" = "/admin/config/services/resource_config"
 *   }
 * )
 */
class ResourceConfig extends ConfigEntityBase implements ResourceConfigInterface {

  /**
   * The Resource Config ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Resource Config label.
   *
   * @var string
   */
  protected $label;

  /**
   * The version string for the resource.
   *
   * @var string
   */
  protected $version;

  /**
   * The path for the resource.
   *
   * @var string
   */
  protected $path;

  /**
   * Entity type.
   *
   * @var string
   */
  protected $contentEntityTypeId;

  /**
   * Bundle.
   *
   * @var string
   */
  protected $contentBundleId;

  /**
   * Resource fields.
   *
   * @var \Drupal\restful\ResourceFields\ResourceFieldInterface[]
   */
  protected $resourceFields;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($this->addDefaults($values), $entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function addDefaults(array $values) {
    $default_field = [
      'data' => [
        'column' => 'value',
      ],
      'id' => 'field',
    ];
    if (!empty($values['resourceFields'])) {
      $values['resourceFields'] = array_map(function ($value) use ($default_field) {
        if (!is_array($value)) {
          return $value;
        }
        return NestedArray::mergeDeep($default_field, $value);
      }, $values['resourceFields']);
    }
    return $values;
  }

}

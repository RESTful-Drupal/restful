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
   * @var array
   */
  protected $resourceFields = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct(static::addDefaults($values), $entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function getVersion() {
    return $this->version;
  }

  /**
   * {@inheritdoc}
   */
  public function setVersion($version) {
    $this->version = $version;
  }

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    return $this->path;
  }

  /**
   * {@inheritdoc}
   */
  public function setPath($path) {
    $this->path = $path;
  }

  /**
   * {@inheritdoc}
   */
  public function getContentEntityTypeId() {
    return $this->contentEntityTypeId;
  }

  /**
   * {@inheritdoc}
   */
  public function setContentEntityTypeId($entity_type_id) {
    $this->contentEntityTypeId = $entity_type_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getContentBundleId() {
    return $this->contentBundleId;
  }

  /**
   * {@inheritdoc}
   */
  public function setContentBundleId($bundle_id) {
    $this->contentBundleId = $bundle_id;
  }

  /**
   * {@inheritdoc}
   */
  public static function addDefaults(array $values) {
    $default_field = [
      'data' => [
        'column' => 'value',
      ],
      'id' => 'field',
    ];
    if (!empty($values['resourceFields'])) {
      $values['resourceFields'] = array_map(function ($value) use ($default_field) {
        if (!is_array($value)) {
          // Avoid PHP blowing up.
          return $value;
        }
        $defaults = NestedArray::mergeDeep($default_field, $value);

        // If the field has a callback, that makes it a callback resource field.
        if (!empty($defaults['callback'])) {
          $defaults['id'] = 'callback';
          unset($defaults['data']);
        }

        // If the process callbacks are empty, remove them.
        if (isset($defaults['processCallbacks']) && !$defaults['processCallbacks']) {
          unset($defaults['processCallbacks']);
        }

        return $defaults;
      }, $values['resourceFields']);
    }
    return $values;
  }

}

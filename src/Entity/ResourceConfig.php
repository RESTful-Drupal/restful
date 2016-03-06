<?php

/**
 * @file
 * Contains \Drupal\restful\Entity\ResourceConfig.
 */

namespace Drupal\restful\Entity;

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

}

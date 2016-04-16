<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\Deriver\ResourceDeriver.
 */

namespace Drupal\restful\Plugin\Deriver;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a resource plugin definition for every entity type.
 *
 * @see \Drupal\restful\Plugin\restful\resource\EntityResource
 */
class ResourceDeriver implements ContainerDeriverInterface {

  /**
   * List of derivative definitions.
   *
   * @var array
   */
  protected $derivatives;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs an ResourceDeriver object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    /* @var EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');
    return new static($entity_type_manager);
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinition($derivative_id, $base_definition) {
    if (!isset($this->derivatives)) {
      $this->getDerivativeDefinitions($base_definition);
    }
    if (isset($this->derivatives[$derivative_id])) {
      return $this->derivatives[$derivative_id];
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_definition) {
    if (!isset($this->derivatives)) {
      $this->derivatives = [];
      // Add in the default plugin configuration and the resource type.
      /* @var \Drupal\restful\ResourceConfigInterface[] $resource_configs */
      $resource_configs = $this
        ->entityTypeManager
        ->getStorage('resource_config')
        ->loadMultiple();
      foreach ($resource_configs as $entity_id => $entity) {
        $entity_type_id = $entity->getContentEntityTypeId();
        $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
        $this->derivatives[$entity_id] = [
          'id' => $entity->id(),
          'entity_type' => $entity_type_id,
          'bundle' => $entity->getContentBundleId(),
          'serialization_class' => $entity_type->getClass(),
          'label' => $entity->label(),
          'uri_paths' => [
            'canonical' => sprintf('/%s/{%s}', $entity->getPath(), $entity_type_id),
            'https://www.drupal.org/link-relations/create' => "/entity/" . $entity->getContentEntityTypeId(),
          ],
        ];

        $this->derivatives[$entity_id] += $base_definition;
      }
    }
    return $this->derivatives;
  }

}

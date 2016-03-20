<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\restful\resource\RestfulResource.
 */

namespace Drupal\restful\Plugin\rest\resource;

use Drupal\rest\Plugin\rest\resource\EntityResource;

/**
 * Represents entities as resources.
 *
 * @RestResource(
 *   id = "restful_entity",
 *   label = @Translation("RESTful Entity"),
 *   serialization_class = "Drupal\Core\Entity\Entity",
 *   deriver = "Drupal\restful\Plugin\Deriver\ResourceDeriver",
 *   uri_paths = {
 *     "canonical" = "/entity/{entity_type}/{entity}",
 *     "https://www.drupal.org/link-relations/create" = "/entity/{entity_type}"
 *   }
 * )
 *
 * @see \Drupal\restful\Plugin\Deriver\ResourceDeriver
 */
class RestfulResource extends EntityResource {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, \Psr\Log\LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
  }

  /**
   * {@inheritdoc}
   */
  protected function getBaseRoute($canonical_path, $method) {
    $route = parent::getBaseRoute($canonical_path, $method);

    $route->setRequirement('_permission', 'access RESTful resources');

    return $route;
  }

}

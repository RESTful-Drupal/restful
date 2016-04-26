<?php

/**
 * @file
 * Contains \Drupal\restful\Routing\ResourceRoutes.
 */

namespace Drupal\restful\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\rest\Plugin\Type\ResourcePluginManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for REST-style routes.
 */
class ResourceRoutes extends RouteSubscriberBase {

  /**
   * The plugin manager for REST plugins.
   *
   * @var \Drupal\rest\Plugin\Type\ResourcePluginManager
   */
  protected $manager;

  /**
   * The Drupal configuration factory.
   *
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a RouteSubscriber object.
   *
   * @param \Drupal\rest\Plugin\Type\ResourcePluginManager $manager
   *   The resource plugin manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The configuration factory holding resource settings.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(ResourcePluginManager $manager, EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger) {
    $this->manager = $manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
  }

  /**
   * Alters existing routes for a specific collection.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection for adding routes.
   *
   * @return array
   */
  protected function alterRoutes(RouteCollection $collection) {
    $enabled_resources = $this->entityTypeManager->getStorage('resource_config')->loadMultiple();

    // Iterate over all enabled resource plugins.
    foreach ($enabled_resources as $id => $resource_config) {
      $plugin = $this->manager->getInstance(['id' => 'restful_entity:' . $id]);

      foreach ($plugin->routes() as $name => $route) {
        // @todo: Are multiple methods possible here?
        $methods = $route->getMethods();
        // Only expose routes where the method is enabled in the configuration.
        if ($methods && ($method = $methods[0]) && $method) {
          $route->setRequirement('_access_rest_csrf', 'TRUE');
          $definition = $plugin->getPluginDefinition();
          if ($method != 'POST') {
            // Make sure that the matched route is for the correct bundle.
            $route->setRequirement('_entity_type', $definition['entity_type']);
            $route->setRequirement('_bundle', $definition['bundle']);
          }
          // TODO Copy from the Drupal\rest\Routing\ResourceRoutes::alterRoutes.
          $collection->add("rest.$name", $route);
        }
      }
    }
  }

}

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
   * @return array
   */
  protected function alterRoutes(RouteCollection $collection) {
    $routes = array();
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
//          // Check that authentication providers are defined.
//          if (empty($enabled_methods[$method]['supported_auth']) || !is_array($enabled_methods[$method]['supported_auth'])) {
//            $this->logger->error('At least one authentication provider must be defined for resource @id', array(':id' => $id));
//            continue;
//          }
//
//          // Check that formats are defined.
//          if (empty($enabled_methods[$method]['supported_formats']) || !is_array($enabled_methods[$method]['supported_formats'])) {
//            $this->logger->error('At least one format must be defined for resource @id', array(':id' => $id));
//            continue;
//          }
//
//          // If the route has a format requirement, then verify that the
//          // resource has it.
//          $format_requirement = $route->getRequirement('_format');
//          if ($format_requirement && !in_array($format_requirement, $enabled_methods[$method]['supported_formats'])) {
//            continue;
//          }
//
//          // The configuration seems legit at this point, so we set the
//          // authentication provider and add the route.
//          $route->setOption('_auth', $enabled_methods[$method]['supported_auth']);
          $routes["rest.$name"] = $route;
          $collection->add("rest.$name", $route);
        }
      }
    }
  }

}

<?php

/**
 * @file
 * Contains \Drupal\Tests\restful\Unit\Routing\ResourceRoutesTest.
 */

namespace Drupal\Tests\restful\Unit\Routing;

use Drupal\restful\Routing\ResourceRoutes;
use Drupal\Tests\restful\Kernel\RestfulDrupalTestBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class ResourceRoutesTest.
 *
 * @package Drupal\Tests\restful\Unit\Routing
 *
 * @coversDefaultClass \Drupal\restful\Routing\ResourceRoutes
 * @group RESTful
 */
class ResourceRoutesTest extends RestfulDrupalTestBase {

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  public static $modules = [
    'node',
    'restful',
    'restful_examples',
    'rest',
    'serialization',
    'system',
    'user',
  ];

  /**
   * Tests the route generation based on the existing resource configs.
   *
   * It's not a testing best practice to test a protected method. But in this
   * case we don't want to go through the pain of testing the public ::routes
   * since it's already tested by the parent class in core.
   *
   * @covers ::alterRoutes
   */
  public function testAlterRoutes() {
    // Add a resource config object.
    $base_path = $this->getRandomGenerator()->name();
    $this->entityTypeManager->getStorage('resource_config')->create([
      'id' => 'articles.v1.0',
      'contentEntityTypeId' => 'node',
      'contentBundleId' => 'article',
      'path' => $base_path,
    ])->save();
    $resource_routes = new ResourceRoutes($this->manager, $this->entityTypeManager, $this->logger);
    $route_collection = new RouteCollection();

    $reflection = new \ReflectionObject($resource_routes);
    $method = $reflection->getMethod('alterRoutes');
    $method->setAccessible(TRUE);

    $method->invokeArgs($resource_routes, [$route_collection]);
    $route_iterator = $route_collection->getIterator();
    while ($route = $route_iterator->current()) {
      // Check the altered routes.
      foreach ($route->getMethods() as $method) {
        if ($method == 'POST') {
          $this->assertEquals('/entity/node', $route->getPath());
        }
        else {
          $this->assertEquals('/' . $base_path . '/{node}', $route->getPath());
        }
      }
      $route_iterator->next();
    }
  }

}

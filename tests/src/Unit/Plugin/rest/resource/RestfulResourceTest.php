<?php

/**
 * @file
 * Contains \Drupal\Tests\restful\Unit\Plugin\rest\resource\RestfulResourceTest.
 */

namespace Drupal\Tests\restful\Unit\Plugin\rest\resource;

use Drupal\Tests\UnitTestCase;

/**
 * Class RestfulResourceTest.
 *
 * @package Drupal\Tests\restful\Unit\Plugin\rest\resource
 *
 * @coversDefaultClass \Drupal\restful\Plugin\rest\resource\RestfulResource
 * @group RESTful
 */
class RestfulResourceTest extends UnitTestCase {

  /**
   * Test the route generation.
   *
   * It's not a testing best practice to test a protected method. But in this
   * case we don't want to go through the pain of testing the public ::routes
   * since it's already tested by the parent class in core.
   *
   * @covers ::getBaseRoute
   */
  public function testGetBaseRoute() {
    $base_plugin = $this
      ->getMockBuilder('\Drupal\restful\Plugin\rest\resource\RestfulResource')
      ->disableOriginalConstructor()
      ->setMethods(['getPluginDefinition'])
      ->getMock();
    $entity_type = $this->getRandomGenerator()->name();
    $canonical_path = $this->getRandomGenerator()->name();
    $base_plugin->expects($this->once())
      ->method('getPluginDefinition')
      ->will($this->returnValue(['entity_type' => $entity_type]));

    $reflection = new \ReflectionObject($base_plugin);
    $method = $reflection->getMethod('getBaseRoute');
    $method->setAccessible(TRUE);

    $route = $method->invokeArgs($base_plugin, [$canonical_path, $this->getRandomGenerator()->name()]);
    $this->assertEquals('/' . $canonical_path, $route->getPath());
    $this->assertEquals('access RESTful resources', $route->getRequirement('_permission'));
    $parameters = $route->getOption('parameters');
    $this->assertArrayEquals([$entity_type => ['type' => 'entity:' . $entity_type]], $parameters);
  }

}

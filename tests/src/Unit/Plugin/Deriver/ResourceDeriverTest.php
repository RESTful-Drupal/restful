<?php

/**
 * @file
 * Contains \Drupal\Tests\restful\Unit\Plugin\Deriver\ResourceDeriverTest.
 */

namespace Drupal\Tests\restful\Unit\Plugin\Deriver;
use Drupal\restful\Plugin\Deriver\ResourceDeriver;
use Drupal\Tests\UnitTestCase;

/**
 * Class ResourceDeriverTest.
 *
 * @package Drupal\Tests\restful\Plugin\Deriver
 *
 * @coversDefaultClass \Drupal\restful\Plugin\Deriver\ResourceDeriver
 *
 * @group RESTful
 */
class ResourceDeriverTest extends UnitTestCase {

  /**
   * Tests the create method.
   *
   * @covers ::create
   */
  public function testCreate() {
    $entity_type_manager = $this->getMock('\Drupal\Core\Entity\EntityTypeManagerInterface');
    $container = $this->getMockBuilder('\Symfony\Component\DependencyInjection\ContainerInterface')
      ->setMethods(['get'])
      ->getMockForAbstractClass();
    $container->expects($this->once())
      ->method('get')
      ->with($this->equalTo('entity_type.manager'))
      ->will($this->returnValue($entity_type_manager));
    $deriver = ResourceDeriver::create($container, []);
    $this->assertInstanceOf('\Drupal\restful\Plugin\Deriver\ResourceDeriver', $deriver);
  }

}

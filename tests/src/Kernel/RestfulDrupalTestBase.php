<?php

/**
 * @file
 * Contains \Drupal\Tests\restful\Kernel\RestfulDrupalTestBase.
 */
namespace Drupal\Tests\restful\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Class RestfulDrupalTestBase.
 *
 * @package Drupal\Tests\restful\Kernel
 */
class RestfulDrupalTestBase extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  public static $modules = [
    'restful',
    'restful_examples',
    'rest',
    'serialization',
    'system',
  ];

  /**
   * RESTful logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Resource plugin manager.
   *
   * @var \Drupal\rest\Plugin\Type\ResourcePluginManager
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Add the logger to the test base class.
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->manager = $this->container->get('plugin.manager.rest');
    $this->logger = $this->container->get('logger.channel.rest');
  }

}

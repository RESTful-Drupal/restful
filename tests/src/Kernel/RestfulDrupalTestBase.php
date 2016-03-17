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
   * RESTful logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  public static $modules = ['restful', 'restful_examples'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Add the logger to the test base class.
    $this->logger = $this->container->get('logger.channel.rest');
  }

}

<?php

/**
 * @file
 * Contains \Drupal\restful\Tests\Kernel\ConfigEntityTest.
 */

namespace Drupal\Tests\restful\Kernel;

/**
 * Tests storage and loading of restful config entities.
 *
 * @group RESTful
 */
class ConfigEntityTest extends RestfulDrupalTestBase {

  /**
   * The entity storage for restful config entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->storage = $this->container->get('entity_type.manager')->getStorage('resource_config');
  }

  /**
   * Tests that an empty rule configuration can be saved.
   */
  public function testSavingEntity() {
    $config_entity = $this->storage->create([
      'id' => 'test_rule',
    ]);
    // This will make sure that the empty entity saves and meets the schema.
    $config_entity->save();
  }

}

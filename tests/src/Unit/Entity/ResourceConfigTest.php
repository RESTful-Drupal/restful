<?php

/**
 * @file
 * Contains \Drupal\Tests\restful\Unit\Entity\ResourceConfigTest.
 */

namespace Drupal\Tests\restful\Unit\Entity;

use Drupal\restful\Entity\ResourceConfig;
use Drupal\Tests\UnitTestCase;


/**
 * @coversDefaultClass \Drupal\restful\Entity\ResourceConfig
 * @group RESTful
 */
class ResourceConfigTest extends UnitTestCase {

  /**
   * Tests the addDefaults.
   *
   * @covers ::addDefaults
   *
   * @dataProvider addDefaultsProvider
   */
  public function testAddDefaults($values, $values_defaults) {
    $this->assertArrayEquals(ResourceConfig::addDefaults($values), $values_defaults);
  }

  /**
   * Provider for defaults.
   *
   * @return array
   *   The data.
   */
  public function addDefaultsProvider() {
    $base = [
      'id' => 'articles.v1.0',
      'label' => 'Articles',
      'version' => 'v1.0',
      'contentEntityTypeId' => 'node',
      'contentBundleId' => 'article',
      'resourceFields' => [
        'title' => [
          'publicName' => 'title',
          'data' => ['field' => 'title'],
        ],
        'shortDescription' => [
          'publicName' => 'shortDescription',
          'data' => ['field' => 'body', 'column' => 'summary'],
          'processCallbacks' => ['\Drupal\editor\EditorXssFilter\Standard::filterXss'],
        ],
        'randomNum' => [
          'publicName' => 'randomNum',
          'callback' => '\Drupal\editor\EditorXssFilter\Standard::filterXss',
        ],
      ],
    ];
    $base_expected = $base;
    $base_expected['resourceFields']['title']['data']['column'] = 'value';
    $base_expected['resourceFields']['title']['id'] = 'field';
    $base_expected['resourceFields']['shortDescription']['id'] = 'field';
    $base_expected['resourceFields']['randomNum']['id'] = 'callback';

    $data = [[$base, $base_expected], [$base, $base_expected]];
    $data[1][0]['resourceFields']['title']['column'] = 'blah!';
    $data[1][1]['resourceFields']['title']['column'] = 'blah!';
    $data[1][0]['resourceFields']['shortDescription']['processCallbacks'] = [];
    unset($data[1][1]['resourceFields']['shortDescription']['processCallbacks']);

    return $data;
  }

  /**
   * Test setters and getters.
   *
   * @covers ::setVersion
   * @covers ::getVersion
   * @covers ::setPath
   * @covers ::getPath
   * @covers ::setContentEntityTypeId
   * @covers ::getContentEntityTypeId
   * @covers ::setContentBundleId
   * @covers ::getContentBundleId
   *
   * @dataProvider settersAndGettersProvider
   */
  public function testSettersAndGetters($mutator, $accessor, $value) {
    $entity = new ResourceConfig([], NULL);
    $entity->{$mutator}($value);
    $this->assertEquals($value, $entity->{$accessor}());
  }

  /**
   * Provider for the setters test.
   *
   * @return array
   *   The data.
   */
  public function settersAndGettersProvider() {
    return [
      [
        'setVersion',
        'getVersion',
        sprintf('v%d.%d', mt_rand(1, 10), mt_rand(1, 10)),
      ],
      ['setPath', 'getPath', $this->getRandomGenerator()->name()],
      [
        'setContentEntityTypeId',
        'getContentEntityTypeId',
        $this->getRandomGenerator()->name(),
      ],
      [
        'setContentBundleId',
        'getContentBundleId',
        $this->getRandomGenerator()->name(),
      ],
    ];
  }

}

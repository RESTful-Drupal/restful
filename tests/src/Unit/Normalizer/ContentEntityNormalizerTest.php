<?php

/**
 * @file
 * Contains \Drupal\Tests\restful\Unit\Normalizer\ContentEntityNormalizerTest.
 */

namespace Drupal\Tests\restful\Unit\Normalizer;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\restful\Normalizer\ContentEntityNormalizer;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\restful\Normalizer\ContentEntityNormalizer
 * @group RESTful
 */
class ContentEntityNormalizerTest extends UnitTestCase {

  /**
   * The mock entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * The mock link manager.
   *
   * @var \Drupal\rest\LinkManager\LinkManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $linkManager;

  /**
   * The mock module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $moduleHandler;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $requestStack;

  /**
   * The mock serializer.
   *
   * @var \Symfony\Component\Serializer\SerializerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $serializer;

  /**
   * The normalizer under test.
   *
   * @var \Drupal\restful\Normalizer\ContentEntityNormalizer
   */
  protected $contentEntityNormalizer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->entityManager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $this->linkManager = $this->getMock('Drupal\rest\LinkManager\LinkManagerInterface');
    $this->moduleHandler = $this->getMock('\Drupal\Core\Extension\ModuleHandlerInterface');
    $this->requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack', array('getCurrentRequest'));
    $request_mock = $this->getMock('Symfony\Component\HttpFoundation\Request');
    $request_mock->query = $this->getMock('Symfony\Component\HttpFoundation\ParameterBag');
    $request_mock->query
      ->method('get')
      ->with('fields')
      ->will($this->returnValue('field_1,field_2'));
    $this->requestStack
      ->method('getCurrentRequest')
      ->will($this->returnValue($request_mock));
    $this->contentEntityNormalizer = new ContentEntityNormalizer($this->linkManager, $this->entityManager, $this->moduleHandler, $this->requestStack);
    $this->serializer = $this->getMockBuilder('Symfony\Component\Serializer\Serializer')
      ->disableOriginalConstructor()
      ->setMethods(array('normalize'))
      ->getMock();
    $this->contentEntityNormalizer->setSerializer($this->serializer);
  }

  /**
   * Tests the normalize() method.
   *
   * @covers ::normalize
   */
  public function testNormalize() {
    $this->serializer->expects($this->any())
      ->method('normalize')
      ->with($this->containsOnlyInstancesOf('Drupal\Core\Field\FieldItemListInterface'), 'test_format')
      // Stub result using the arguments.
      ->will($this->returnCallback(function (FieldItemListInterface $argument, $format) {
        return [
          $argument->getFieldDefinition()->getName() => [
            'value' => $format,
          ],
        ];
      }));

    $definitions = array(
      'field_1' => $this->createMockFieldListItem('field_1'),
      'field_2' => $this->createMockFieldListItem('field_2', FALSE),
      'field_3' => $this->createMockFieldListItem('field_3'),
    );
    $content_entity_mock = $this->createMockForContentEntity($definitions);

    $normalized = $this->contentEntityNormalizer->normalize($content_entity_mock, 'test_format');

    // field_1 is included and should be present.
    $this->assertArrayHasKey('field_1', $normalized);
    // Make sure we get the stubbed value for the field normalizer.
    $this->assertEquals(['value' => 'test_format'], $normalized['field_1']);
    // field_2 is included but access is denied. It should not be included.
    $this->assertArrayNotHasKey('field_2', $normalized);
    // field_2 is not included.
    $this->assertArrayNotHasKey('field_3', $normalized);
  }

  /**
   * Creates a mock field list item.
   *
   * @param string $name
   *   Field name.
   * @param bool $access
   *   Stubbed access.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface|\PHPUnit_Framework_MockObject_MockObject
   *   The mock.
   */
  protected function createMockFieldListItem($name, $access = TRUE, $user_context = NULL) {
    $mock = $this->getMock('Drupal\Core\Field\FieldItemListInterface');
    $mock->expects($this->any())
      ->method('access')
      ->with('view', $user_context)
      ->will($this->returnValue($access));

    $definition_mock = $this->getMock('\Drupal\Core\Field\FieldDefinitionInterface');
    $definition_mock->expects($this->any())
      ->method('getName')
      ->will($this->returnValue($name));

    $mock->expects($this->any())
      ->method('getFieldDefinition')
      ->will($this->returnValue($definition_mock));

    return $mock;
  }

  /**
   * Creates a mock content entity.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface[]|\PHPUnit_Framework_MockObject_MockObject[] $definitions
   *   The field definitions.
   *
   * @return \PHPUnit_Framework_MockObject_MockObject
   *   The mock.
   */
  public function createMockForContentEntity($definitions) {
    $content_entity_mock = $this->getMockBuilder('Drupal\Core\Entity\ContentEntityBase')
      ->disableOriginalConstructor()
      ->setMethods([
        'getFields',
        'isNew',
        'hasLinkTemplate',
        'id',
        'bundle',
        'getEntityType',
        'getKey',
      ])
      ->getMockForAbstractClass();

    $content_entity_mock->expects($this->once())
      ->method('getFields')
      ->will($this->returnValue($definitions));

    $content_entity_mock->expects($this->any())
      ->method('isNew')
      ->will($this->returnValue(FALSE));

    $content_entity_mock->expects($this->any())
      ->method('id')
      ->will($this->returnValue(1));

    $content_entity_mock->expects($this->any())
      ->method('bundle')
      ->will($this->returnValue('page'));

    $content_entity_mock->expects($this->any())
      ->method('getEntityType')
      ->will($this->returnValue($content_entity_mock));

    $content_entity_mock->expects($this->any())
      ->method('getKey')
      ->with($this->anything())
      ->will($this->returnValue('vid'));

    return $content_entity_mock;
  }

}

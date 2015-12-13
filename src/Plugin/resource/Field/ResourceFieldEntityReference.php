<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Field\ResourceFieldEntityReference.
 */

namespace Drupal\restful\Plugin\resource\Field;

use Drupal\restful\Exception\BadRequestException;
use Drupal\restful\Exception\InternalServerErrorException;
use Drupal\restful\Http\HttpHeaderBag;
use Drupal\restful\Http\Request;
use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\DataInterpreter\DataInterpreterInterface;
use Drupal\restful\Plugin\resource\DataProvider\DataProviderResource;

/**
 * Class ResourceFieldEntityReference.
 *
 * @package Drupal\restful\Plugin\resource\Field
 */
class ResourceFieldEntityReference extends ResourceFieldEntity implements ResourceFieldEntityReferenceInterface {

  /**
   * Property where the ID should be retrieved from.
   *
   * If empty, the entity ID will be used. It's either the property or Field API
   * field name.
   *
   * @var string
   */
  protected $referencedIdProperty;

  /**
   * Constructs a ResourceFieldEntityReference.
   *
   * @param array $field
   *   Contains the field values.
   *
   * @param RequestInterface $request
   *   The request.
   */
  public function __construct(array $field, RequestInterface $request) {
    parent::__construct($field, $request);
    if (!empty($field['referencedIdProperty'])) {
      $this->referencedIdProperty = $field['referencedIdProperty'];
    }
    // TODO: Document referencedIdProperty.
  }

  /**
   * {@inheritdoc}
   */
  public function preprocess($value) {
    if (!$value) {
      // If value is empty, return NULL, so no new entity will be created.
      return NULL;
    }

    $cardinality = $this->getCardinality();
    if ($cardinality != 1 && !is_array($value)) {
      // If the field is entity reference type and its cardinality larger than
      // 1 set value to an array.
      $value = explode(',', $value);
    }

    if ($cardinality != 1 && ResourceFieldBase::isArrayNumeric($value)) {
      // Set the cardinality to 1 to process each value as a single value item.
      $this->setCardinality(1);
      // For multiple value items, pre-process them separately.
      $values = array();
      foreach ($value as $item) {
        $values[] = $this->preprocess($item);
      }
      $this->setCardinality($cardinality);
      return $values;
    }
    // If the provided value is the ID to the referenced entity, then do not do
    // a sub-request.
    if (!is_array($value) || empty($value['body'])) {
      // Allow to pass an array with the ID instead of the ID directly.
      return (!empty($value['id']) && array_keys($value) == array('id')) ? $value['id'] : $value;
    }

    /* @var ResourceFieldCollectionInterface $merged_value */
    $merged_value = $this->mergeEntityFromReference($value);
    return $merged_value->getInterpreter()->getWrapper()->getIdentifier();
  }

  /**
   * Helper function; Create an entity from a a sub-resource.
   *
   * @param mixed $value
   *   The single value for the sub-request.
   *
   * @return mixed
   *   The value to set using the wrapped property.
   */
  protected function mergeEntityFromReference($value) {
    $resource = $this->getResource();
    if (empty($resource) || empty($value['body'])) {
      // Field is not defined as "resource", which means it only accepts an
      // integer as a valid value.
      // Or, we are passing an integer and cardinality is 1. That means that we
      // are passing the ID of the referenced entity. Hence setting the new
      // value to the reference field.
      return $value;
    }

    // Get the resource data provider and make the appropriate operations.
    // We need to create a RequestInterface object for the sub-request.
    $resource_data_provider = DataProviderResource::init(static::subRequest($value), $resource['name'], array(
      $resource['majorVersion'],
      $resource['minorVersion'],
    ));

    // We are always dealing with the single value.
    $merged = $resource_data_provider->merge(static::subRequestId($value), $value['body']);
    return reset($merged);
  }

  /**
   * {@inheritdoc}
   */
  public static function subRequest(array $value) {
    if (empty($value['request'])) {
      throw new BadRequestException('Malformed body payload. Missing "request" key for the sub-request.');
    }
    if (empty($value['request']['method'])) {
      throw new BadRequestException('Malformed body payload. Missing "method" int the "request" key for the sub-request.');
    }
    $request_user_info = $value['request'] + array(
      'path' => NULL,
      'query' => array(),
      'csrf_token' => NULL,
    );

    $headers = empty($request_user_info['headers']) ? array() : $request_user_info['headers'];
    $request_user_info['headers'] = new HttpHeaderBag($headers);
    $request_user_info['via_router'] = FALSE;
    $request_user_info['cookies'] = $_COOKIE;
    $request_user_info['files'] = $_FILES;
    $request_user_info['server'] = $_SERVER;

    return Request::create(
      $request_user_info['path'],
      $request_user_info['query'],
      $request_user_info['method'],
      $request_user_info['headers'],
      $request_user_info['via_router'],
      $request_user_info['csrf_token'],
      $request_user_info['cookies'],
      $request_user_info['files'],
      $request_user_info['server']
    );
  }

  /**
   * Get the ID of the resource this write sub-request is for.
   *
   * @param array $value
   *   The value provided for this sub-request item.
   *
   * @return string
   *   The ID.
   */
  protected static function subRequestId($value) {
    if ($value['request']['method'] == RequestInterface::METHOD_POST) {
      // If the request is for post, then disregard any possible ID.
      return NULL;
    }
    return empty($value['id']) ? NULL : $value['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function value(DataInterpreterInterface $interpreter) {
    $value = $this->decorated->value($interpreter);
    if (isset($value)) {
      // Let the decorated resolve callbacks.
      return $value;
    }

    // Check user has access to the property.
    if (!$this->access('view', $interpreter)) {
      return NULL;
    }

    $resource = $this->getResource();
    // If the field definition does not contain a resource, or it is set
    // explicitly to fullView FALSE, then return only the entity ID.
    if (
      $resource ||
      (!empty($resource) && $resource['fullView'] !== FALSE) ||
      $this->getFormatter()
    ) {
      // Let the resource embedding to the parent class.
      return parent::value($interpreter);
    }

    // Since this is a reference field (a field that points to other entities,
    // we can know for sure that the property wrappers are instances of
    // \EntityDrupalWrapper or lists of them.
    $property_wrapper = $this->propertyWrapper($interpreter);
    if (!$property_wrapper->value()) {
      // If there is no referenced entity, return.
      return NULL;
    }

    // If this is a multivalue field, then call recursively on the items.
    if ($property_wrapper instanceof \EntityListWrapper) {
      $values = array();
      foreach ($property_wrapper->getIterator() as $item_wrapper) {
        $values[] = $this->referencedId($item_wrapper);
      }
      return $values;
    }
    /* @var $property_wrapper \EntityDrupalWrapper */
    return $this->referencedId($property_wrapper);
  }

  /**
   * Helper function to get the referenced entity ID.
   *
   * @param \EntityDrupalWrapper $property_wrapper
   *   The wrapper for the referenced entity.
   *
   * @return mixed
   *   The ID.
   */
  protected function referencedId($property_wrapper) {
    $identifier = $property_wrapper->getIdentifier();
    if (!$this->referencedIdProperty) {
      return $identifier;
    }
    try {
      return $identifier ? $property_wrapper->{$this->referencedIdProperty}->value() : NULL;
    }
    catch (\EntityMetadataWrapperException $e) {
      // An exception will be raised for broken entity reference fields.
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getRequest() {
    return $this->decorated->getRequest();
  }

  /**
   * {@inheritdoc}
   */
  public function setRequest(RequestInterface $request) {
    $this->decorated->setRequest($request);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinition() {
    return $this->decorated->getDefinition();
  }

}

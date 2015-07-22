<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Field\ResourceFieldEntityReference.
 */

namespace Drupal\restful\Plugin\resource\Field;

use Drupal\restful\Http\HttpHeaderBag;
use Drupal\restful\Http\Request;
use Drupal\restful\Plugin\resource\DataInterpreter\DataInterpreterInterface;
use Drupal\restful\Plugin\resource\DataProvider\DataProviderResource;

class ResourceFieldEntityReference extends ResourceFieldEntity implements ResourceFieldEntityReferenceInterface {

  /**
   * {@inheritdoc}
   */
  public function preprocess($value) {
    if (!$value) {
      // If value is empty, return NULL, so no new entity will be created.
      return NULL;
    }

    $field_info = field_info_field($this->getProperty());
    if ($field_info['cardinality'] != 1 && !is_array($value)) {
      // If the field is entity reference type and its cardinality larger than
      // 1 set value to an array.
      $value = explode(',', $value);
    }

    // If the provided value is the ID to the referenced entity, then do not do
    // a sub-request.
    if (!is_array($value) || empty($value['values'])) {
      return $value;
    }

    $value = static::subRequestId($this->mergeEntityFromReference($value));

    return ($field_info['cardinality'] == 1 && is_array($value)) ? reset($value) : $value;
  }

  /**
   * Helper function; Create an entity from a a sub-resource.
   *
   * @param mixed $value
   *   The value passed in the request.
   *
   * @return mixed
   *   The value to set using the wrapped property.
   */
  protected function mergeEntityFromReference($value) {
    // TODO: Move this to the docs. The payload for the resource has changed.
    // The sub-request applies to all of the values for a given field. It is not
    // possible to have a different request for the different values of a multi
    // value field.
    // The structure of the payload send for a public field declared as a
    // resource is now like:
    // 1. array(
    //   'request' => array(
    //     'method' => 'PATCH',
    //     'headers' => array('foo' => 'bar'),
    //     'csrf_token' => 'abcde',
    //   ),
    //   'values' => array(
    //     array(
    //       'id' => 1, // This is the referenced entity ID. Hardcoded for now.
    //       'my-description' => 'Lorem ipsum',
    //       'another-int' => 2,
    //     ),
    //     array(
    //       'id' => 5,
    //       'my-description' => 'Only the description has changed this time.',
    //     ),
    //   ),
    // );
    // 2. array(1, 5); <-- To set a multi value reference field. Without
    //    updating the underlying entities.
    // 3. 5 <-- To set a single value reference field. Without updating the
    //    underlying entities.

    $field_info = field_info_field($this->getProperty());

    $resource = $this->getResource();
    $cardinality = $field_info['cardinality'];
    if (empty($resource) || empty($value['values'])) {
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

    // Return the entity ID that was created.
    if ($cardinality == 1) {
      // Single value.
      return $resource_data_provider->merge(static::subRequestId($value['values'][0]), $value['values'][0]);
    }

    // Multiple values.
    $return = array();
    foreach ($value['values'] as $value_item) {
      // If there is only the 'id' public property, then only assign the new
      // reference.
      if (array_keys($value_item) == array('id')) {
        $return[] = $value_item;
        continue;
      }
      $merged = $resource_data_provider->merge(static::subRequestId($value_item), $value_item);
      $return[] = reset($merged);
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public static function subRequest(array $value) {
    $value['request'] = empty($value['request']) ? array() : $value['request'];
    $request_user_info = $value['request'] + array(
      'method' => restful()->getRequest()->getMethod(),
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
   *   The array of values provided for this sub-request.
   *
   * @return int
   *   The ID.
   */
  protected static function subRequestId(array $value) {
    if (!static::isArrayNumeric($value)) {
      return empty($value['id']) ? NULL : $value['id'];
    }
    $output = array();
    foreach ($value as $item) {
      $output[] = static::subRequestId($item);
    }
    return $output;
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
    // explicitly to full_view FALSE, then return only the entity ID.
    if (
      $resource ||
      (!empty($resource) && $resource['full_view'] !== FALSE) ||
      $this->getFormatter()
    ) {
      // Let the resource embedding to the parent class.
      return parent::value($interpreter);
    }

    // Since this is a reference field (a field that points to other entities,
    // we can know for sure that the property wrappers are instances of
    // \EntityDrupalWrapper or lists of them.
    $property_wrapper = $this->propertyWrapper($interpreter);

    // If this is a multivalue field, then call recursively on the items.
    if ($property_wrapper instanceof \EntityListWrapper) {
      $values = array();
      foreach ($property_wrapper->getIterator() as $item_wrapper) {
        /** @var $item_wrapper \EntityDrupalWrapper */
        $values[] = $item_wrapper->getIdentifier();
      }
      return $values;
    }
    /** @var $property_wrapper \EntityDrupalWrapper */
    return $property_wrapper->getIdentifier();
  }

}

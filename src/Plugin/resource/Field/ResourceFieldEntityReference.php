<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Field\ResourceFieldEntityReference.
 */

namespace Drupal\restful\Plugin\resource\Field;

use Drupal\restful\Http\HttpHeaderBag;
use Drupal\restful\Http\Request;
use Drupal\restful\Http\RequestInterface;
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

    $value = $this->mergeEntityFromReference($value);

    return $value;
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
    // The structure of the payload send for a public field declared as a
    // resource is now like:

    array(
      'request' => array(
        'method' => 'PATCH',
        'headers' => array('foo' => 'bar'),
        'csrf_token' => 'abcde',
      ),
      'values' => array(
        array(
          'id' => 1,
          'my-description' => 'Lorem ipsum',
          'another-int' => 2,
        ),
        array(
          'id' => 5,
          'my-description' => 'Only the description has changed this time.',
        ),
      ),
    );

    $field_info = field_info_field($this->getProperty());

    $resource = $this->getResource();
    $cardinality = $field_info['cardinality'];
    if (
      empty($resource) ||
      (
        $cardinality == 1 &&
        !is_array($value)
      )
    ) {
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
      $resource['major_version'],
      $resource['minor_version'],
    ));

    // Return the entity ID that was created.
    if ($cardinality == 1) {
      // Single value.
      return $resource_data_provider->merge(static::subRequestId($value), $value);
    }

    // Multiple values.
    $return = array();
    foreach ($value as $value_item) {
      $return[] = $resource_data_provider->merge(static::subRequestId($value), $value_item);
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public static function subRequest(array $value) {
    $request_user_info = $value['request'] + array(
      'path' => NULL,
      'query' => array(),
    );

    if (empty($request_user_info['method'])) {
      // If there is no id for the sub request, then it's a POST.
      if (!static::subRequestId($value)) {
        // TODO: Move the METHOD_* constants to RequestInterface.
        $request_user_info['method'] = Request::METHOD_POST;
      }
      else {
        $request_user_info['method'] = Request::METHOD_PATCH;
      }
    }
    if (!empty($request_user_info['headers']) || is_array($request_user_info['headers'])) {
      $request_user_info['headers'] = new HttpHeaderBag($request_user_info['headers']);
    }
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
   * Get the ID of the resource this sub-request is for.
   *
   * @param array $value
   *   The array of values provided for this sub-request.
   *
   * @return int
   *   The ID.
   */
  protected static function subRequestId(array $value) {
    return $value['id'];
  }

}

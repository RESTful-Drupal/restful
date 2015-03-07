<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\DataProvider\DataProviderResourceInterface.
 */

namespace Drupal\restful\Plugin\resource\DataProvider;

use Drupal\restful\Http\RequestInterface;

interface DataProviderResourceInterface extends DataProviderInterface {

  /**
   * Creates a new DataProviderResource object from the resource info.
   *
   * @param RequestInterface $request
   *   The request.
   * @param string $resource_name
   *   The resource name.
   * @param array $version
   *   The first position is the major version, the second is the minor version.
   *
   * @return DataProviderResourceInterface
   *   The data provider.
   */
  public static function init(RequestInterface $request, $resource_name, array $version);

  /**
   * Create or update an item based on the payload.
   *
   * @param mixed $identifier
   *   The resource item identifier.
   * @param mixed $object
   *   The payload.
   *
   * @return mixed
   *   The identifier of the created or updated resource item.
   */
  public function merge($identifier, $object);

}

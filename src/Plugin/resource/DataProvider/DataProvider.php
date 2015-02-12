<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\DataProvider\DataProvider.
 */

namespace Drupal\restful\Plugin\resource\DataProvider;

use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\Field\ResourceFieldCollectionInterface;

abstract class DataProvider implements DataProviderInterface {

  /**
   * The field definitions.
   *
   * @var ResourceFieldCollectionInterface
   */
  protected $fieldDefinitions;

  /**
   * The request
   *
   * @var RequestInterface
   */
  protected $request;

  /**
   * Gets the request.
   *
   * @return RequestInterface
   *   The request
   */
  public function getRequest() {
    return $this->request;
  }

}

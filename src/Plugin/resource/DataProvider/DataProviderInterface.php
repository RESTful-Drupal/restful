<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\DataProvider\DataProviderInterface.
 */

namespace Drupal\restful\Plugin\resource\DataProvider;

use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\CrudInterface;
use Drupal\restful\Plugin\resource\Field\ResourceFieldInterface;

interface DataProviderInterface extends CrudInterface {

  /**
   * Gets the range.
   *
   * @return int
   *   The range
   */
  public function getRange();

  /**
   * Sets the range.
   *
   * @param int $range
   *   The range
   */
  public function setRange($range);

  /**
   * Gets the authenticated account.
   *
   * @return object
   *   The fully loaded user account.
   */
  public function getAccount();

  /**
   * Sets the authenticated account.
   *
   * @param object $account
   *   The fully loaded user account.
   */
  public function setAccount($account);

  /**
   * Gets the request.
   *
   * @return RequestInterface
   *   The request.
   */
  public function getRequest();

  /**
   * Sets the request.
   *
   * @param RequestInterface $request
   *   The request.
   */
  public function setRequest(RequestInterface $request);

  /**
   * Get the language code.
   *
   * @return string
   *   The language code
   */
  public function getLangCode();

  /**
   * Sets the language code.
   *
   * @param string $langcode
   *   The language code.
   */
  public function setLangCode($langcode);

  /**
   * Gets the data provider options.
   *
   * @return array
   *   The array of options for the data provider.
   */
  public function getOptions();

  /**
   * Adds the options in the provided array to the data provider options.
   *
   * @param array $options
   *   The array of options for the data provider.
   */
  public function addOptions(array $options);

  /**
   * Gets the entity context.
   *
   * @param mixed $identifier
   *   The ID.
   */
  public function getContext($identifier);

  /**
   * Generates the canonical path for a given path.
   *
   * @param string $path
   *   The aliased path.
   *
   * @return string
   *   The canonical path.
   */
  public function canonicalPath($path);

  /**
   * Checks if the provided field can be used with the current method.
   *
   * @param ResourceFieldInterface $resource_field
   *   The field.
   *
   * @return bool
   *   TRUE if acces is granted. FALSE otherwise.
   */
  public function methodAccess(ResourceFieldInterface $resource_field);

  /**
   * Sets the options.
   *
   * @param array $options
   *   The options to set.
   */
  public function setOptions(array $options);

  /**
   * Returns the ID to render for the current index GET request.
   *
   * @return array
   *   Numeric array containing the identifiers to be sent to viewMultiple.
   */
  public function getIndexIds();

  /**
   * Set the resource path.
   *
   * @param string $resource_path
   *   The resource path.
   */
  public function setResourcePath($resource_path);

  /**
   * Get the resource path.
   *
   * @return string
   *   The resource path.
   */
  public function getResourcePath();

}

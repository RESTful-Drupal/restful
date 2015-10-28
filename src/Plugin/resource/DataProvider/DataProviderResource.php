<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\DataProvider\DataProviderResource.
 */

namespace Drupal\restful\Plugin\resource\DataProvider;

use Drupal\Component\Plugin\PluginBase;
use Drupal\restful\Http\Request;
use Drupal\restful\Plugin\resource\ResourceInterface;
use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\Field\ResourceFieldCollectionInterface;
use Drupal\restful\Plugin\ResourcePluginManager;

/**
 * This data provider creates a resource and uses it to access the data.
 *
 * Class DataProviderResource
 * @package Drupal\restful\Plugin\resource\DataProvider
 */
class DataProviderResource extends DataProvider implements DataProviderResourceInterface {

  /**
   * The referenced resource.
   *
   * @var ResourceInterface
   */
  protected $resource;

  /**
   * The referenced data provider.
   *
   * @var DataProviderInterface
   */
  protected $referencedDataProvider;

  /**
   * Constructor.
   *
   * @param RequestInterface $request
   *   The request.
   * @param ResourceFieldCollectionInterface $field_definitions
   *   The field definitions.
   * @param object $account
   *   The authenticated account.
   * @param string $plugin_id
   *   The resource ID.
   * @param string $resource_path
   *   The resource path.
   * @param array $options
   *   The plugin options for the data provider.
   * @param string $langcode
   *   (Optional) The entity language code.
   * @param ResourceInterface $resource
   *   The referenced resource.
   */
  public function __construct(RequestInterface $request, ResourceFieldCollectionInterface $field_definitions, $account, $plugin_id, $resource_path, array $options, $langcode = NULL, ResourceInterface $resource = NULL) {
    $resource->setRequest($request);
    $this->resource = $resource;
    $this->referencedDataProvider = $resource->getDataProvider();
    parent::__construct($request, $field_definitions, $account, $plugin_id, $resource_path, $options, $langcode);
  }

  /**
   * {@inheritdoc}
   */
  public static function init(RequestInterface $request, $resource_name, array $version, $resource_path = NULL) {
    /* @var ResourceInterface $resource */
    $instance_id = $resource_name . PluginBase::DERIVATIVE_SEPARATOR . $version[0] . '.' . $version[1];
    $resource = restful()
      ->getResourceManager()
      ->getPluginCopy($instance_id, Request::create('', array(), RequestInterface::METHOD_GET));
    $plugin_definition = $resource->getPluginDefinition();
    $resource->setPath($resource_path);
    return new static($request, $resource->getFieldDefinitions(), $resource->getAccount(), $resource->getPluginId(), $resource->getPath(), $plugin_definition['dataProvider'], static::getLanguage(), $resource);
  }

  /**
   * {@inheritdoc}
   */
  public function index() {
    return $this->referencedDataProvider->index();
  }

  /**
   * {@inheritdoc}
   */
  public function getIndexIds() {
    return $this->referencedDataProvider->getIndexIds();
  }

  /**
   * {@inheritdoc}
   */
  public function create($object) {
    return $this->referencedDataProvider->create($object);
  }

  /**
   * {@inheritdoc}
   */
  public function view($identifier) {
    return $this->referencedDataProvider->view($identifier);
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $identifiers) {
    return $this->referencedDataProvider->viewMultiple($identifiers);
  }

  /**
   * {@inheritdoc}
   */
  public function update($identifier, $object, $replace = FALSE) {
    return $this->referencedDataProvider->update($identifier, $object, $replace);
  }

  /**
   * {@inheritdoc}
   */
  public function remove($identifier) {
    $this->referencedDataProvider->remove($identifier);
  }

  /**
   * {@inheritdoc}
   */
  public function merge($identifier, $object) {
    if (!$identifier) {
      return $this->referencedDataProvider->create($object);
    }
    $replace = ($method = $this->getRequest()->getMethod()) ? $method == RequestInterface::METHOD_PUT : FALSE;
    return $this->referencedDataProvider->update($identifier, $object, $replace);
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return $this->referencedDataProvider->count();
  }

  /**
   * {@inheritdoc}
   */
  protected function initDataInterpreter($identifier) {
    return NULL;
  }

}

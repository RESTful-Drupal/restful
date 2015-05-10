<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Decorators\ResourceDecoratorBase.
 */

namespace Drupal\restful\Plugin\resource\Decorators;


use Drupal\Component\Plugin\PluginBase;
use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\DataProvider\DataProviderInterface;
use Drupal\restful\Plugin\resource\Field\ResourceFieldCollectionInterface;
use Drupal\restful\Plugin\resource\ResourceInterface;

abstract class ResourceDecoratorBase extends PluginBase implements ResourceDecoratorInterface {

  /**
   * The decorated resource.
   *
   * @var ResourceInterface
   */
  protected $subject;

  /**
   * {@inheritdoc}
   */
  public function getDecoratedResource() {
    return $this->subject;
  }

  /**
   * {@inheritdoc}
   */
  public function getPrimaryResource() {
    $resource = $this->getDecoratedResource();
    while ($resource instanceof ResourceDecoratorInterface) {
      $resource = $resource->getDecoratedResource();
    }
    return $resource;
  }

  /**
   * {@inheritdoc}
   */
  public function dataProviderFactory() {
    return $this->subject->dataProviderFactory();
  }

  /**
   * Proxy method to get the account from the rateLimitManager.
   *
   * {@inheritdoc}
   */
  public function getAccount($cache = TRUE) {
    return $this->subject->getAccount($cache);
  }

  /**
   * Proxy method to get the account from the rateLimitManager.
   *
   * {@inheritdoc}
   */
  public function setAccount($account) {
    $this->subject->setAccount($account);
    $this->getDataProvider()->setAccount($account);
  }

  /**
   * {@inheritdoc}
   */
  public function getRequest() {
    return $this->subject->getRequest();
  }

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    return $this->subject->getPath();
  }

  /**
   * {@inheritdoc}
   */
  public function setPath($path) {
    $this->subject->setPath($path);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDefinitions() {
    return $this->subject->getFieldDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function getDataProvider() {
    return $this->subject->getDataProvider();
  }

  /**
   * {@inheritdoc}
   */
  public function setDataProvider(DataProviderInterface $data_provider = NULL) {
    $this->subject->setDataProvider($data_provider);
  }

  /**
   * {@inheritdoc}
   */
  public function getResourceName() {
    return $this->subject->getResourceName();
  }

  /**
   * {@inheritdoc}
   */
  public function process() {
    return $this->subject->process();
  }

  /**
   * {@inheritdoc}
   */
  public function controllersInfo() {
    return $this->subject->controllersInfo();
  }

  /**
   * {@inheritdoc}
   */
  public function getControllers() {
    return $this->subject->getControllers();
  }

  /**
   * {@inheritdoc}
   */
  public function index($path) {
    return $this->subject->index($path);
  }

  /**
   * {@inheritdoc}
   */
  public function view($path) {
    return $this->subject->view($path);
  }

  /**
   * {@inheritdoc}
   */
  public function create($path) {
    return $this->subject->create($path);
  }

  /**
   * {@inheritdoc}
   */
  public function update($path) {
    return $this->subject->update($path);
  }

  /**
   * {@inheritdoc}
   */
  public function replace($path) {
    return $this->subject->replace($path);
  }

  /**
   * {@inheritdoc}
   */
  public function remove($path) {
    $this->subject->remove($path);
  }

  /**
   * {@inheritdoc}
   */
  public function getVersion() {
    return $this->subject->getVersion();
  }

  /**
   * {@inheritdoc}
   */
  public function versionedUrl($path = '', $options = array(), $version_string = TRUE) {
    return $this->subject->versionedUrl($path, $options, $version_string);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->subject->getConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->subject->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return $this->subject->defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return $this->subject->calculateDependencies();
  }

  /**
   * {@inheritdoc}
   */
  public function setRequest(RequestInterface $request) {
    $this->subject->setRequest($request);
    // Make sure that the request is updated in the data provider.
    $this->getDataProvider()->setRequest($request);
  }

  /**
   * {@inheritdoc}
   */
  public function access() {
    return $this->subject->access();
  }

  /**
   * {@inheritdoc}
   */
  public function getControllerFromPath($path = NULL, ResourceInterface $resource = NULL) {
    return $this->subject->getControllerFromPath($path, $resource ?: $this);
  }

  /**
   * {@inheritdoc}
   */
  public function getResourceMachineName() {
    return $this->subject->getResourceMachineName();
  }

  /**
   * {@inheritdoc}
   *
   * This is a decorated resource, get proxy the request until you reach the
   * annotated resource.
   */
  public function getPluginDefinition() {
    return $this->subject->getPluginDefinition();
  }

  /**
   * {@inheritdoc}
   *
   * This is a decorated resource, set proxy the request until you reach the
   * annotated resource.
   */
  public function setPluginDefinition(array $plugin_definition) {
    $this->subject->setPluginDefinition($plugin_definition);
    if (!empty($plugin_definition['dataProvider'])) {
      $this->getDataProvider()->addOptions($plugin_definition['dataProvider']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function enable() {
    $this->subject->enable();
  }

  /**
   * {@inheritdoc}
   */
  public function disable() {
    $this->subject->disable();
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return $this->subject->isEnabled();
  }

  /**
   * {@inheritdoc}
   */
  public function setFieldDefinitions(ResourceFieldCollectionInterface $field_definitions) {
    return $this->subject->setFieldDefinitions($field_definitions);
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl(array $options = array(), $keep_query = TRUE, RequestInterface $request = NULL) {
    return $this->subject->getUrl($options, $keep_query, $request);
  }

}
